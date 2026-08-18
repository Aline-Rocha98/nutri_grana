<?php

namespace App\Services\Orcamento;

use App\Enum\FormaPagamento;
use App\Enum\ModalidadePagamentoOrcamento;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\StatusOrcamentoServico;
use App\Enum\TipoLancamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\Categoria\Categoria;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Orcamento\OrcamentoServico;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Repositories\Lancamento\LancamentoRepository;
use App\Repositories\Orcamento\OrcamentoServicoRepository;
use App\Services\FaturaCartao\FaturaCartaoService;
use App\Services\Financeiro\ProjetorFluxoCaixa;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrcamentoServicoService
{
    /** @var list<int> */
    private const PARCELAS_MAXIMAS = 12;

    public function __construct(
        private readonly OrcamentoServicoRepository $orcamentoServicoRepository,
        private readonly ProjetorFluxoCaixa $projetorFluxoCaixa,
        private readonly CalculadoraViabilidadeOrcamentoServico $calculadoraViabilidade,
        private readonly MontadorCompromissosOrcamentoServico $montadorCompromissos,
        private readonly LancamentoRepository $lancamentoRepository,
        private readonly ContaBancariaRepository $contaBancariaRepository,
        private readonly FaturaCartaoService $faturaCartaoService,
    ) {}

    public function listarDoUsuario(int $idUsuario, ?Carbon $referencia = null): Collection
    {
        $referencia = ($referencia ?? Carbon::today())->copy()->startOfDay();

        return $this->orcamentoServicoRepository
            ->listarPorUsuario($idUsuario)
            ->map(function (OrcamentoServico $orcamento) use ($idUsuario, $referencia) {
                $this->sincronizarStatus($orcamento, $referencia);

                return $this->anexarSimulacao($orcamento, $idUsuario, $referencia);
            });
    }

    public function criar(int $idUsuario, array $dados): OrcamentoServico
    {
        $orcamento = $this->orcamentoServicoRepository->criar([
            'id_usuario' => $idUsuario,
            'status' => StatusOrcamentoServico::EmAnalise,
            ...$this->normalizarDadosCotacao($idUsuario, $dados),
        ]);

        $orcamento->load(['categoria', 'subcategoria', 'contaBancaria', 'cartaoCredito']);

        return $this->anexarSimulacao($orcamento, $idUsuario);
    }

    public function atualizar(OrcamentoServico $orcamento, int $idUsuario, array $dados): OrcamentoServico
    {
        $this->garantirPropriedade($orcamento, $idUsuario);
        $this->garantirEditavel($orcamento);

        $atualizado = $this->orcamentoServicoRepository->atualizar(
            $orcamento,
            $this->normalizarDadosCotacao($idUsuario, $dados),
        );

        $atualizado->load(['categoria', 'subcategoria', 'contaBancaria', 'cartaoCredito']);

        return $this->anexarSimulacao($atualizado, $idUsuario);
    }

    public function excluir(OrcamentoServico $orcamento, int $idUsuario): void
    {
        $this->garantirPropriedade($orcamento, $idUsuario);

        if ($orcamento->status === StatusOrcamentoServico::Aprovada) {
            throw ValidationException::withMessages([
                'orcamento' => 'Não é possível excluir uma cotação aprovada com compromissos gerados.',
            ]);
        }

        $this->orcamentoServicoRepository->excluir($orcamento);
    }

    public function aprovar(OrcamentoServico $orcamento, int $idUsuario, array $dados): OrcamentoServico
    {
        $this->garantirPropriedade($orcamento, $idUsuario);
        $this->sincronizarStatus($orcamento);

        if (! $orcamento->status?->podeAprovar()) {
            throw ValidationException::withMessages([
                'status' => 'Somente cotações em análise podem ser aprovadas.',
            ]);
        }

        if ($orcamento->data_validade?->copy()->startOfDay()->lt(Carbon::today())) {
            throw ValidationException::withMessages([
                'data_validade' => 'Esta cotação está expirada e não pode ser aprovada.',
            ]);
        }

        $pagamento = $this->normalizarPagamentoAprovacao($idUsuario, $dados);

        $this->gerarCompromissos($orcamento, $idUsuario, $pagamento);

        $aprovado = $this->orcamentoServicoRepository->atualizar($orcamento, [
            ...$pagamento,
            'status' => StatusOrcamentoServico::Aprovada,
            'data_aprovacao' => now(),
        ]);

        $aprovado->load(['categoria', 'subcategoria', 'contaBancaria', 'cartaoCredito', 'compromissos']);

        return $this->anexarSimulacao($aprovado, $idUsuario);
    }

    public function recusar(OrcamentoServico $orcamento, int $idUsuario): OrcamentoServico
    {
        $this->garantirPropriedade($orcamento, $idUsuario);
        $this->sincronizarStatus($orcamento);

        if (! $orcamento->status?->podeRecusar()) {
            throw ValidationException::withMessages([
                'status' => 'Somente cotações em análise podem ser recusadas.',
            ]);
        }

        $recusado = $this->orcamentoServicoRepository->atualizar($orcamento, [
            'status' => StatusOrcamentoServico::Recusada,
            'data_recusa' => now(),
        ]);

        $recusado->load(['categoria', 'subcategoria']);

        return $this->anexarSimulacao($recusado, $idUsuario);
    }

    /**
     * @return array<string, mixed>
     */
    public function simular(
        int $idUsuario,
        array $dadosCotacao,
        FormaPagamento $forma = FormaPagamento::ContaBancaria,
        ?int $idContaBancaria = null,
        ?int $idCartaoCredito = null,
        ?Carbon $referencia = null,
    ): array {
        $referencia = ($referencia ?? Carbon::today())->copy()->startOfDay();

        return $this->montarSimulacao(
            $idUsuario,
            (float) $dadosCotacao['valor'],
            $dadosCotacao['data_orcamento'],
            $dadosCotacao['data_validade'],
            $forma,
            ModalidadePagamentoOrcamento::from(
                $dadosCotacao['modalidade_pagamento'] ?? ModalidadePagamentoOrcamento::AVista->value
            ),
            $idContaBancaria,
            $idCartaoCredito,
            $referencia,
        );
    }

    public function anexarSimulacao(
        OrcamentoServico $orcamento,
        int $idUsuario,
        ?Carbon $referencia = null,
    ): OrcamentoServico {
        $referencia = ($referencia ?? Carbon::today())->copy()->startOfDay();

        if ($orcamento->status === StatusOrcamentoServico::EmAnalise) {
            $forma = $orcamento->forma_pagamento instanceof FormaPagamento
                ? $orcamento->forma_pagamento
                : FormaPagamento::from($orcamento->forma_pagamento ?? FormaPagamento::ContaBancaria->value);
            $modalidade = $orcamento->modalidade_pagamento instanceof ModalidadePagamentoOrcamento
                ? $orcamento->modalidade_pagamento
                : ModalidadePagamentoOrcamento::from(
                    $orcamento->modalidade_pagamento ?? ModalidadePagamentoOrcamento::AVista->value
                );

            $idConta = $this->resolverIdContaParaSimulacao(
                $idUsuario,
                $forma,
                $orcamento->id_conta_bancaria !== null ? (int) $orcamento->id_conta_bancaria : null,
            );
            $idCartao = $this->resolverIdCartaoParaSimulacao(
                $idUsuario,
                $forma,
                $orcamento->id_cartao_credito !== null ? (int) $orcamento->id_cartao_credito : null,
            );

            $this->carregarRelacionamentosPagamentoResolvidos(
                $orcamento,
                $idUsuario,
                $forma,
                $idConta,
                $idCartao,
            );

            $simulacao = $this->montarSimulacao(
                $idUsuario,
                (float) $orcamento->valor,
                $orcamento->data_orcamento?->toDateString() ?? $referencia->toDateString(),
                $orcamento->data_validade?->toDateString() ?? $referencia->toDateString(),
                $forma,
                $modalidade,
                $idConta,
                $idCartao,
                $referencia,
            );

            foreach ($simulacao as $chave => $valor) {
                $orcamento->setAttribute($chave, $valor);
            }
        }

        if ($orcamento->status === StatusOrcamentoServico::Aprovada) {
            $orcamento->setAttribute('compromissos_gerados', $orcamento->compromissos()->count());
        }

        return $orcamento;
    }

    public function garantirPropriedade(OrcamentoServico $orcamento, int $idUsuario): void
    {
        if ((int) $orcamento->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Esta cotação não pertence ao usuário autenticado.');
        }
    }

    private function sincronizarStatus(OrcamentoServico $orcamento, ?Carbon $referencia = null): void
    {
        $referencia = ($referencia ?? Carbon::today())->copy()->startOfDay();

        if ($orcamento->status !== StatusOrcamentoServico::EmAnalise) {
            if ($orcamento->status === StatusOrcamentoServico::Aprovada) {
                $this->sincronizarConclusao($orcamento);
            }

            return;
        }

        if ($orcamento->data_validade?->copy()->startOfDay()->lt($referencia)) {
            $this->orcamentoServicoRepository->atualizar($orcamento, [
                'status' => StatusOrcamentoServico::Expirada,
            ]);
        }
    }

    private function sincronizarConclusao(OrcamentoServico $orcamento): void
    {
        $pendentes = $orcamento->compromissos()
            ->whereNotIn('situacao', [
                SituacaoLancamento::Pago->value,
                SituacaoLancamento::Cancelado->value,
            ])
            ->exists();

        if (! $pendentes && $orcamento->compromissos()->exists()) {
            $this->orcamentoServicoRepository->atualizar($orcamento, [
                'status' => StatusOrcamentoServico::Concluida,
                'data_conclusao' => $orcamento->data_conclusao ?? now(),
            ]);
        }
    }

    private function garantirEditavel(OrcamentoServico $orcamento): void
    {
        if (! $orcamento->status?->podeEditar()) {
            throw ValidationException::withMessages([
                'status' => 'Somente cotações em análise podem ser editadas.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function montarSimulacao(
        int $idUsuario,
        float $valor,
        string $dataOrcamento,
        string $dataValidade,
        FormaPagamento $forma,
        ModalidadePagamentoOrcamento $modalidadeEscolhida,
        ?int $idContaBancaria,
        ?int $idCartaoCredito,
        Carbon $referencia,
    ): array {
        $dataValidadeCarbon = Carbon::parse($dataValidade)->startOfDay();
        $parcelasOpcoes = $modalidadeEscolhida === ModalidadePagamentoOrcamento::AVista
            ? [1]
            : range(1, self::PARCELAS_MAXIMAS);

        $dadosBase = [
            'valor' => $valor,
            'data_orcamento' => $dataOrcamento,
            'modalidade_pagamento' => $modalidadeEscolhida->value,
            'forma_pagamento' => $forma->value,
            'total_parcelas' => 1,
            'id_conta_bancaria' => $idContaBancaria,
            'id_cartao_credito' => $idCartaoCredito,
        ];

        $cartao = $this->resolverCartao($idUsuario, $forma, $idCartaoCredito);
        $limiteDisponivel = null;
        $ultrapassaLimiteTotal = false;

        if ($cartao) {
            $usado = $this->lancamentoRepository->somarUsoEmAbertoDoCartao((int) $cartao->id_cartao_credito);
            $limiteDisponivel = round(max(0, (float) $cartao->limite_total - $usado), 2);
            $ultrapassaLimiteTotal = $valor > $limiteDisponivel;
        }

        $horizonte = $this->resolverHorizonte($valor, $dataOrcamento, $dataValidadeCarbon, $parcelasOpcoes, $referencia, $cartao);

        $projecaoSem = $this->projetorFluxoCaixa->projetar(
            $idUsuario,
            $horizonte,
            [],
            $referencia,
        );

        $saldoContaSelecionada = $forma === FormaPagamento::ContaBancaria && $idContaBancaria !== null
            ? $this->resolverSaldoConta($idUsuario, (int) $idContaBancaria)
            : null;

        $cenariosMontados = [];

        foreach ($parcelasOpcoes as $totalParcelas) {
            $modalidade = $totalParcelas > 1
                ? ModalidadePagamentoOrcamento::Parcelado
                : ModalidadePagamentoOrcamento::AVista;

            $dadosCenario = [
                ...$dadosBase,
                'modalidade_pagamento' => $modalidade->value,
                'total_parcelas' => $totalParcelas,
            ];

            $montagem = $this->montadorCompromissos->montar($dadosCenario, $cartao, $referencia);
            $ultrapassaLimite = $forma === FormaPagamento::CartaoCredito && $ultrapassaLimiteTotal;

            $projecaoCom = $this->projetorFluxoCaixa->projetar(
                $idUsuario,
                $horizonte,
                $montagem['compromissos'],
                $referencia,
            );

            $cenariosMontados[] = [
                'total_parcelas' => $totalParcelas,
                'projecao_com' => $projecaoCom,
                'contexto' => [
                    'modalidade' => $modalidade,
                    'forma' => $forma,
                    'total_parcelas' => $montagem['total_parcelas'],
                    'valor_parcela' => $montagem['valor_parcela'],
                    'limite_disponivel_cartao' => $limiteDisponivel,
                    'cartao_nome' => $cartao?->nome,
                    'ultrapassa_limite_cartao' => $ultrapassaLimite,
                ],
            ];
        }

        return $this->calculadoraViabilidade->simularCenarios(
            $valor,
            $projecaoSem,
            $dataValidadeCarbon,
            $cenariosMontados,
            $modalidadeEscolhida,
            $referencia,
            $saldoContaSelecionada,
        );
    }

    private function resolverHorizonte(
        float $valor,
        string $dataOrcamento,
        Carbon $dataValidade,
        array $parcelasOpcoes,
        Carbon $referencia,
        ?CartaoCredito $cartao = null,
    ): Carbon {
        $maxParcelas = max($parcelasOpcoes);
        $dados = [
            'valor' => $valor,
            'data_orcamento' => $dataOrcamento,
            'modalidade_pagamento' => ModalidadePagamentoOrcamento::Parcelado->value,
            'forma_pagamento' => FormaPagamento::ContaBancaria->value,
            'total_parcelas' => $maxParcelas,
        ];

        $montagem = $this->montadorCompromissos->montar($dados, $cartao, $referencia);
        $horizonte = $dataValidade->gt($montagem['data_ultimo_compromisso'])
            ? $dataValidade
            : $montagem['data_ultimo_compromisso'];

        return $this->projetorFluxoCaixa->resolverHorizonte($referencia, $horizonte);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizarDadosCotacao(int $idUsuario, array $dados): array
    {
        $this->validarDatas($dados);
        $this->validarCategorias($idUsuario, $dados);
        $pagamento = $this->normalizarPagamentoSimulacao($idUsuario, $dados);

        return [
            'descricao' => $dados['descricao'],
            'fornecedor' => $dados['fornecedor'] ?? null,
            'valor' => $dados['valor'],
            'data_orcamento' => $dados['data_orcamento'],
            'data_validade' => $dados['data_validade'],
            'observacao' => $dados['observacao'] ?? null,
            'id_categoria' => $dados['id_categoria'] ?? null,
            'id_subcategoria' => $dados['id_subcategoria'] ?? null,
            ...$pagamento,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizarPagamentoSimulacao(int $idUsuario, array $dados): array
    {
        $modalidade = ModalidadePagamentoOrcamento::from(
            $dados['modalidade_pagamento'] ?? ModalidadePagamentoOrcamento::AVista->value
        );
        $forma = FormaPagamento::from(
            $dados['forma_pagamento'] ?? FormaPagamento::ContaBancaria->value
        );

        $idConta = null;
        $idCartao = null;

        if ($forma === FormaPagamento::ContaBancaria) {
            $idConta = (int) ($dados['id_conta_bancaria'] ?? 0);
            if ($idConta <= 0 || ! $this->contaPertenceAoUsuario($idUsuario, $idConta)) {
                throw ValidationException::withMessages([
                    'id_conta_bancaria' => 'Selecione uma conta bancária válida.',
                ]);
            }
        }

        if ($forma === FormaPagamento::CartaoCredito) {
            $idCartao = (int) ($dados['id_cartao_credito'] ?? 0);
            if ($idCartao <= 0 || ! $this->cartaoPertenceAoUsuario($idUsuario, $idCartao)) {
                throw ValidationException::withMessages([
                    'id_cartao_credito' => 'Selecione um cartão de crédito válido.',
                ]);
            }
        }

        return [
            'modalidade_pagamento' => $modalidade,
            'total_parcelas' => $modalidade === ModalidadePagamentoOrcamento::Parcelado ? 2 : 1,
            'forma_pagamento' => $forma,
            'id_conta_bancaria' => $idConta,
            'id_cartao_credito' => $idCartao,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizarPagamentoAprovacao(int $idUsuario, array $dados): array
    {
        $modalidade = ModalidadePagamentoOrcamento::from($dados['modalidade_pagamento']);
        $forma = FormaPagamento::from($dados['forma_pagamento']);
        $totalParcelas = $modalidade === ModalidadePagamentoOrcamento::Parcelado
            ? (int) ($dados['total_parcelas'] ?? 0)
            : 1;

        if ($modalidade === ModalidadePagamentoOrcamento::Parcelado && ($totalParcelas < 2 || $totalParcelas > 48)) {
            throw ValidationException::withMessages([
                'total_parcelas' => 'Informe entre 2 e 48 parcelas.',
            ]);
        }

        $idConta = null;
        $idCartao = null;

        if ($forma === FormaPagamento::ContaBancaria) {
            $idConta = (int) ($dados['id_conta_bancaria'] ?? 0);
            if ($idConta <= 0 || ! $this->contaPertenceAoUsuario($idUsuario, $idConta)) {
                throw ValidationException::withMessages([
                    'id_conta_bancaria' => 'Selecione uma conta bancária válida.',
                ]);
            }
        }

        if ($forma === FormaPagamento::CartaoCredito) {
            $idCartao = (int) ($dados['id_cartao_credito'] ?? 0);
            if ($idCartao <= 0 || ! $this->cartaoPertenceAoUsuario($idUsuario, $idCartao)) {
                throw ValidationException::withMessages([
                    'id_cartao_credito' => 'Selecione um cartão de crédito válido.',
                ]);
            }
        }

        return [
            'modalidade_pagamento' => $modalidade,
            'total_parcelas' => $totalParcelas,
            'forma_pagamento' => $forma,
            'id_conta_bancaria' => $idConta,
            'id_cartao_credito' => $idCartao,
        ];
    }

    private function gerarCompromissos(OrcamentoServico $orcamento, int $idUsuario, array $pagamento): void
    {
        $forma = $pagamento['forma_pagamento'];
        $cartao = null;

        if ($forma === FormaPagamento::CartaoCredito) {
            $cartao = CartaoCredito::query()->find($pagamento['id_cartao_credito']);
            $usado = $this->lancamentoRepository->somarUsoEmAbertoDoCartao((int) $cartao->id_cartao_credito);
            $limiteDisponivel = round(max(0, (float) $cartao->limite_total - $usado), 2);

            if ((float) $orcamento->valor > $limiteDisponivel) {
                throw ValidationException::withMessages([
                    'id_cartao_credito' => 'O valor da cotação ultrapassa o limite disponível do cartão.',
                ]);
            }
        }

        $dadosMontagem = [
            'valor' => (float) $orcamento->valor,
            'data_orcamento' => $orcamento->data_orcamento?->toDateString(),
            'modalidade_pagamento' => $pagamento['modalidade_pagamento']->value,
            'forma_pagamento' => $forma->value,
            'total_parcelas' => $pagamento['total_parcelas'],
        ];

        $montagem = $this->montadorCompromissos->montar($dadosMontagem, $cartao);
        $grupo = (string) Str::uuid();
        $idCategoria = $orcamento->idCategoriaCompromisso();
        $total = count($montagem['compromissos']);

        foreach ($montagem['compromissos'] as $compromisso) {
            $payload = [
                'id_usuario' => $idUsuario,
                'id_orcamento_servico' => $orcamento->id_orcamento_servico,
                'descricao' => sprintf(
                    '%s (%d/%d)',
                    $orcamento->descricao,
                    $compromisso['parcela'],
                    $total,
                ),
                'valor' => $compromisso['valor'],
                'data_vencimento' => $compromisso['data']->toDateString(),
                'tipo' => TipoLancamento::Despesa,
                'forma_pagamento' => $forma,
                'id_conta_bancaria' => $pagamento['id_conta_bancaria'],
                'id_cartao_credito' => $pagamento['id_cartao_credito'],
                'situacao' => SituacaoLancamento::Previsto,
                'id_categoria' => $idCategoria,
                'observacao' => $orcamento->observacao,
                'parcela_atual' => $compromisso['parcela'],
                'total_parcelas' => $total,
                'id_grupo_parcela' => $grupo,
            ];

            if ($forma === FormaPagamento::CartaoCredito && $cartao) {
                $fatura = $this->faturaCartaoService->buscarOuCriar($cartao, $compromisso['data']);
                $payload['id_fatura_cartao'] = $fatura->id_fatura_cartao;
            }

            $this->lancamentoRepository->criar($payload);
        }
    }

    private function validarDatas(array $dados): void
    {
        $dataOrcamento = Carbon::parse($dados['data_orcamento'])->startOfDay();
        $dataValidade = Carbon::parse($dados['data_validade'])->startOfDay();

        if ($dataValidade->lt($dataOrcamento)) {
            throw ValidationException::withMessages([
                'data_validade' => 'A data de validade deve ser igual ou posterior à data da cotação.',
            ]);
        }
    }

    private function validarCategorias(int $idUsuario, array $dados): void
    {
        if (! empty($dados['id_categoria'])) {
            $existe = Categoria::query()
                ->where('id_categoria', $dados['id_categoria'])
                ->where('id_usuario', $idUsuario)
                ->exists();

            if (! $existe) {
                throw ValidationException::withMessages([
                    'id_categoria' => 'Selecione uma categoria válida.',
                ]);
            }
        }

        if (! empty($dados['id_subcategoria'])) {
            $existe = Categoria::query()
                ->where('id_categoria', $dados['id_subcategoria'])
                ->where('id_usuario', $idUsuario)
                ->whereNotNull('id_categoria_pai')
                ->exists();

            if (! $existe) {
                throw ValidationException::withMessages([
                    'id_subcategoria' => 'Selecione uma subcategoria válida.',
                ]);
            }
        }
    }

    private function resolverSaldoConta(int $idUsuario, int $idContaBancaria): float
    {
        $conta = $this->contaBancariaRepository->buscarParaUsuario($idContaBancaria, $idUsuario);

        if (! $conta) {
            return 0.0;
        }

        $movimentado = $this->contaBancariaRepository->saldoMovimentado($idContaBancaria);

        return round((float) $conta->saldo_inicial + $movimentado, 2);
    }

    private function resolverIdContaParaSimulacao(
        int $idUsuario,
        FormaPagamento $forma,
        ?int $idConta,
    ): ?int {
        if ($forma !== FormaPagamento::ContaBancaria) {
            return null;
        }

        if ($idConta !== null && $this->contaPertenceAoUsuario($idUsuario, $idConta)) {
            return $idConta;
        }

        $conta = ContaBancaria::query()
            ->where('id_usuario', $idUsuario)
            ->where('arquivada', SimNao::Nao)
            ->orderByRaw("CASE WHEN padrao_desconto = 'S' THEN 0 ELSE 1 END")
            ->orderBy('nome')
            ->first();

        return $conta ? (int) $conta->id_conta_bancaria : null;
    }

    private function resolverIdCartaoParaSimulacao(
        int $idUsuario,
        FormaPagamento $forma,
        ?int $idCartao,
    ): ?int {
        if ($forma !== FormaPagamento::CartaoCredito) {
            return null;
        }

        if ($idCartao !== null && $this->cartaoPertenceAoUsuario($idUsuario, $idCartao)) {
            return $idCartao;
        }

        $cartao = CartaoCredito::query()
            ->where('id_usuario', $idUsuario)
            ->where('arquivada', SimNao::Nao)
            ->orderByRaw("CASE WHEN padrao = 'S' THEN 0 ELSE 1 END")
            ->orderBy('nome')
            ->first();

        return $cartao ? (int) $cartao->id_cartao_credito : null;
    }

    private function carregarRelacionamentosPagamentoResolvidos(
        OrcamentoServico $orcamento,
        int $idUsuario,
        FormaPagamento $forma,
        ?int $idConta,
        ?int $idCartao,
    ): void {
        if ($forma === FormaPagamento::ContaBancaria && $idConta !== null) {
            $conta = $this->contaBancariaRepository->buscarParaUsuario($idConta, $idUsuario);

            if ($conta) {
                $conta->setAttribute(
                    'saldo_atual',
                    $this->resolverSaldoConta($idUsuario, $idConta),
                );
                $orcamento->setRelation('contaBancaria', $conta);
            }

            return;
        }

        if ($forma === FormaPagamento::CartaoCredito && $idCartao !== null) {
            $cartao = CartaoCredito::query()
                ->where('id_cartao_credito', $idCartao)
                ->where('id_usuario', $idUsuario)
                ->first();

            if ($cartao) {
                $orcamento->setRelation('cartaoCredito', $cartao);
            }
        }
    }

    private function resolverCartao(
        int $idUsuario,
        FormaPagamento $forma,
        ?int $idCartaoCredito,
    ): ?CartaoCredito {
        if ($forma !== FormaPagamento::CartaoCredito || ! $idCartaoCredito) {
            return null;
        }

        return CartaoCredito::query()
            ->where('id_cartao_credito', $idCartaoCredito)
            ->where('id_usuario', $idUsuario)
            ->first();
    }

    private function contaPertenceAoUsuario(int $idUsuario, int $idConta): bool
    {
        return ContaBancaria::query()
            ->where('id_conta_bancaria', $idConta)
            ->where('id_usuario', $idUsuario)
            ->exists();
    }

    private function cartaoPertenceAoUsuario(int $idUsuario, int $idCartao): bool
    {
        return CartaoCredito::query()
            ->where('id_cartao_credito', $idCartao)
            ->where('id_usuario', $idUsuario)
            ->exists();
    }
}
