<?php

namespace App\Services\Lancamento;

use App\Enum\FormaPagamento;
use App\Enum\FrequenciaRecorrencia;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\Categoria\Categoria;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Repositories\Lancamento\LancamentoRepository;
use App\Services\FaturaCartao\FaturaCartaoService;
use App\Services\Orcamento\VerificadorUltrapassagemOrcamento;
use App\Services\Renda\RendaGeracaoService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LancamentoService
{
    public function __construct(
        private readonly LancamentoRepository $lancamentoRepository,
        private readonly RecorrenciaService $recorrenciaService,
        private readonly RendaGeracaoService $rendaGeracaoService,
        private readonly FaturaCartaoService $faturaCartaoService,
        private readonly ContaBancariaRepository $contaBancariaRepository,
        private readonly VerificadorUltrapassagemOrcamento $verificadorUltrapassagemOrcamento,
    ) {}

    public function listarDoMes(int $idUsuario, int $ano, int $mes, array $filtros = [], int $porPagina = 20): LengthAwarePaginator 
    {
        $this->recorrenciaService->gerarParaMes($idUsuario, $ano, $mes);
        $this->rendaGeracaoService->gerarParaMes($idUsuario, $ano, $mes);

        return $this->lancamentoRepository->listarDoMes($idUsuario, $ano, $mes, $filtros, $porPagina);
    }

    public function totaisDoMes(int $idUsuario, int $ano, int $mes): array
    {
        $this->recorrenciaService->gerarParaMes($idUsuario, $ano, $mes);
        $this->rendaGeracaoService->gerarParaMes($idUsuario, $ano, $mes);

        $totais = $this->lancamentoRepository->totaisDoMes($idUsuario, $ano, $mes);
        $pendentesAnteriores = $this->lancamentoRepository->totaisPendentesAnteriores($idUsuario, $ano, $mes);

        $totalContas = (float) ContaBancaria::query()
            ->where('id_usuario', $idUsuario)
            ->where('arquivada', SimNao::Nao)
            ->sum('saldo_inicial');

        $totais['total_contas'] = $totalContas;
        $totais['pendentes_anteriores_despesas'] = $pendentesAnteriores['despesas'];
        $totais['saldo'] = $totais['receitas'] - $totais['despesas'];

        return $totais;
    }

    public function criar(int $idUsuario, array $dados): Collection
    {
        $this->validarFormaPagamento($idUsuario, $dados);
        $this->validarCategoria($idUsuario, $dados['id_categoria'] ?? null);
        $this->verificadorUltrapassagemOrcamento->garantirDentroDoLimiteOuConfirmado($idUsuario, $dados);

        $parcelas = (int) ($dados['total_parcelas'] ?? 1);
        $ehRecorrente = ($dados['recorrente'] ?? false) === true
            || ($dados['recorrente'] ?? null) === '1'
            || ($dados['recorrente'] ?? null) === 1;

        if ($ehRecorrente && $parcelas > 1) {
            throw ValidationException::withMessages([
                'recorrente' => 'Um lançamento não pode ser recorrente e parcelado ao mesmo tempo.',
            ]);
        }

        if ($ehRecorrente) {
            return collect([$this->criarRecorrente($idUsuario, $dados)]);
        }

        if ($parcelas > 1) {
            return $this->criarParcelado($idUsuario, $dados, $parcelas);
        }

        return collect([$this->criarSimples($idUsuario, $dados)]);
    }

    public function atualizar(Lancamento $lancamento, int $idUsuario, array $dados): Lancamento
    {
        $this->garantirPropriedade($lancamento, $idUsuario);

        if ($lancamento->ehPaiRecorrencia()) {
            throw ValidationException::withMessages([
                'lancamento' => 'Edite as ocorrências geradas ou cancele a recorrência.',
            ]);
        }

        $this->validarFormaPagamento($idUsuario, $dados);
        $this->validarCategoria($idUsuario, $dados['id_categoria'] ?? null);
        $this->verificadorUltrapassagemOrcamento->garantirDentroDoLimiteOuConfirmado(
            $idUsuario,
            $dados,
            $lancamento,
        );

        $payload = $this->montarDados($idUsuario, $dados);
        $payload = $this->vincularFatura($payload, $dados);

        if (($dados['situacao'] ?? null) === SituacaoLancamento::Pago->value
            || ($dados['situacao'] ?? null) === SituacaoLancamento::Pago) {
            $payload['data_pagamento'] = $dados['data_pagamento']
                ?? ($lancamento->data_pagamento?->toDateString() ?? Carbon::today()->toDateString());
        }

        if (($dados['situacao'] ?? null) === SituacaoLancamento::Pendente->value) {
            $payload['data_pagamento'] = null;
        }

        return $this->lancamentoRepository->atualizar($lancamento, $payload);
    }

    public function alterarSituacao(Lancamento $lancamento, int $idUsuario, SituacaoLancamento $situacao): Lancamento
    {
        $this->garantirPropriedade($lancamento, $idUsuario);

        if ($lancamento->ehRenda()) {
            throw ValidationException::withMessages([
                'situacao' => 'Receitas de renda devem ser confirmadas pelo modal de confirmação.',
            ]);
        }

        $dados = ['situacao' => $situacao];

        if ($situacao === SituacaoLancamento::Pago) {
            $dados['data_pagamento'] = Carbon::today()->toDateString();
        }

        if ($situacao === SituacaoLancamento::Pendente) {
            $dados['data_pagamento'] = null;
        }

        return $this->lancamentoRepository->atualizar($lancamento, $dados);
    }

    public function confirmarReceita(Lancamento $lancamento, int $idUsuario, array $dados): Lancamento
    {
        $this->garantirPropriedade($lancamento, $idUsuario);

        if (! $lancamento->ehRenda()) {
            throw ValidationException::withMessages([
                'lancamento' => 'Este lançamento não é uma receita de renda.',
            ]);
        }

        if ($lancamento->situacao !== SituacaoLancamento::Previsto) {
            throw ValidationException::withMessages([
                'lancamento' => 'Somente receitas previstas podem ser confirmadas.',
            ]);
        }

        return $this->lancamentoRepository->atualizar($lancamento, [
            'valor' => $dados['valor_recebido'],
            'data_pagamento' => $dados['data_recebimento'],
            'situacao' => SituacaoLancamento::Recebido,
        ]);
    }

    public function excluir(Lancamento $lancamento, int $idUsuario, bool $futuras = false): void
    {
        $this->garantirPropriedade($lancamento, $idUsuario);

        if ($lancamento->ehPaiRecorrencia()) {
            $this->lancamentoRepository->atualizar($lancamento, [
                'situacao' => SituacaoLancamento::Cancelado,
            ]);

            if ($futuras) {
                $this->lancamentoRepository->cancelarFuturasDoPai(
                    (int) $lancamento->id_lancamento,
                    Carbon::today()
                );
            }

            return;
        }

        if ($futuras && $lancamento->id_lancamento_pai) {
            $this->lancamentoRepository->cancelarFuturasDoPai(
                (int) $lancamento->id_lancamento_pai,
                Carbon::parse($lancamento->data_vencimento)
            );
            $this->lancamentoRepository->atualizar(
                Lancamento::query()->findOrFail($lancamento->id_lancamento_pai),
                ['situacao' => SituacaoLancamento::Cancelado]
            );

            return;
        }

        $this->lancamentoRepository->excluir($lancamento);
    }

    private function criarSimples(int $idUsuario, array $dados): Lancamento
    {
        $payload = $this->montarDados($idUsuario, $dados);
        $payload = $this->vincularFatura($payload, $dados);

        if (($payload['situacao'] ?? null) === SituacaoLancamento::Pago
            || ($payload['situacao'] ?? null) === SituacaoLancamento::Pago->value) {
            $payload['data_pagamento'] = $dados['data_pagamento'] ?? Carbon::today()->toDateString();
        }

        return $this->lancamentoRepository->criar($payload);
    }

    private function criarRecorrente(int $idUsuario, array $dados): Lancamento
    {
        $frequencia = FrequenciaRecorrencia::from($dados['frequencia_recorrencia']);

        $payload = $this->montarDados($idUsuario, $dados);
        $payload['eh_recorrencia'] = SimNao::Sim;
        $payload['frequencia_recorrencia'] = $frequencia;
        $payload['recorrencia_ate'] = $dados['recorrencia_ate'] ?? null;
        $payload['situacao'] = SituacaoLancamento::Pendente;
        $payload['data_pagamento'] = null;
        $payload['id_fatura_cartao'] = null;

        $pai = $this->lancamentoRepository->criar($payload);

        $dataInicio = Carbon::parse($pai->data_vencimento);
        $ano = (int) $dataInicio->year;
        $mes = (int) $dataInicio->month;
        $this->recorrenciaService->gerarParaMes($idUsuario, $ano, $mes);

        return $pai->refresh();
    }

    private function criarParcelado(int $idUsuario, array $dados, int $totalParcelas): Collection
    {
        if ($totalParcelas < 2 || $totalParcelas > 48) {
            throw ValidationException::withMessages([
                'total_parcelas' => 'O número de parcelas deve ser entre 2 e 48.',
            ]);
        }

        $valorTotal = (float) $dados['valor'];
        $valorParcela = round($valorTotal / $totalParcelas, 2);
        $valores = array_fill(0, $totalParcelas, $valorParcela);
        $valores[$totalParcelas - 1] = round($valorTotal - ($valorParcela * ($totalParcelas - 1)), 2);

        $grupo = (string) Str::uuid();
        $dataBase = Carbon::parse($dados['data_vencimento']);
        $criados = collect();

        for ($i = 0; $i < $totalParcelas; $i++) {
            $dataParcela = $dataBase->copy()->addMonthsNoOverflow($i);
            $payload = $this->montarDados($idUsuario, array_merge($dados, [
                'valor' => $valores[$i],
                'data_vencimento' => $dataParcela->toDateString(),
                'situacao' => SituacaoLancamento::Pendente,
            ]));

            $payload['id_grupo_parcela'] = $grupo;
            $payload['parcela_atual'] = $i + 1;
            $payload['total_parcelas'] = $totalParcelas;
            $payload['descricao'] = sprintf('%s (%d/%d)', $dados['descricao'], $i + 1, $totalParcelas);
            $payload['data_pagamento'] = null;
            $payload = $this->vincularFatura($payload, array_merge($dados, [
                'data_vencimento' => $dataParcela->toDateString(),
            ]));

            $criados->push($this->lancamentoRepository->criar($payload));
        }

        return $criados;
    }

    private function montarDados(int $idUsuario, array $dados): array
    {
        $forma = FormaPagamento::from($dados['forma_pagamento']);
        $situacao = isset($dados['situacao'])
            ? (is_string($dados['situacao']) ? SituacaoLancamento::from($dados['situacao']) : $dados['situacao'])
            : SituacaoLancamento::Pendente;

        return [
            'id_usuario' => $idUsuario,
            'descricao' => $dados['descricao'],
            'valor' => $dados['valor'],
            'data_vencimento' => $dados['data_vencimento'],
            'tipo' => TipoLancamento::from($dados['tipo']),
            'forma_pagamento' => $forma,
            'id_conta_bancaria' => $forma === FormaPagamento::ContaBancaria
                ? ($dados['id_conta_bancaria'] ?? null)
                : null,
            'id_cartao_credito' => $forma === FormaPagamento::CartaoCredito
                ? ($dados['id_cartao_credito'] ?? null)
                : null,
            'situacao' => $situacao,
            'id_categoria' => $dados['id_categoria'] ?? null,
            'observacao' => $dados['observacao'] ?? null,
            'eh_recorrencia' => SimNao::Nao,
        ];
    }

    private function vincularFatura(array $payload, array $dados): array
    {
        if (($payload['forma_pagamento'] ?? null) !== FormaPagamento::CartaoCredito
            && ($payload['forma_pagamento'] ?? null) !== FormaPagamento::CartaoCredito->value) {
            $payload['id_fatura_cartao'] = null;

            return $payload;
        }

        $idCartao = $payload['id_cartao_credito'] ?? null;
        if (!$idCartao) {
            return $payload;
        }

        $cartao = CartaoCredito::query()->find($idCartao);
        if (!$cartao) {
            return $payload;
        }

        $fatura = $this->faturaCartaoService->buscarOuCriar(
            $cartao,
            Carbon::parse($dados['data_vencimento'] ?? $payload['data_vencimento'])
        );

        $payload['id_fatura_cartao'] = $fatura->id_fatura_cartao;

        return $payload;
    }

    private function validarFormaPagamento(int $idUsuario, array $dados): void
    {
        $forma = FormaPagamento::from($dados['forma_pagamento']);

        if ($forma === FormaPagamento::ContaBancaria) {
            if (empty($dados['id_conta_bancaria'])) {
                throw ValidationException::withMessages([
                    'id_conta_bancaria' => 'Selecione a conta bancária.',
                ]);
            }

            $conta = $this->contaBancariaRepository->buscarParaUsuario(
                (int) $dados['id_conta_bancaria'],
                $idUsuario
            );

            if (!$conta) {
                throw ValidationException::withMessages([
                    'id_conta_bancaria' => 'Conta bancária inválida.',
                ]);
            }
        }

        if ($forma === FormaPagamento::CartaoCredito) {
            if (empty($dados['id_cartao_credito'])) {
                throw ValidationException::withMessages([
                    'id_cartao_credito' => 'Selecione o cartão de crédito.',
                ]);
            }

            $cartao = CartaoCredito::query()
                ->where('id_cartao_credito', $dados['id_cartao_credito'])
                ->where('id_usuario', $idUsuario)
                ->first();

            if (!$cartao) {
                throw ValidationException::withMessages([
                    'id_cartao_credito' => 'Cartão de crédito inválido.',
                ]);
            }
        }
    }

    private function validarCategoria(int $idUsuario, mixed $idCategoria): void
    {
        if ($idCategoria === null || $idCategoria === '') {
            return;
        }

        $existe = Categoria::query()
            ->where('id_categoria', $idCategoria)
            ->where(function ($q) use ($idUsuario) {
                $q->where('id_usuario', $idUsuario)
                    ->orWhereNull('id_usuario');
            })
            ->exists();

        if (!$existe) {
            throw ValidationException::withMessages([
                'id_categoria' => 'Categoria inválida.',
            ]);
        }
    }

    private function garantirPropriedade(Lancamento $lancamento, int $idUsuario): void
    {
        if ((int) $lancamento->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Este lançamento não pertence ao usuário autenticado.');
        }
    }
}
