<?php

namespace App\Services\PlanoFinanceiro;

use App\Enum\CorItemPlanoFinanceiro;
use App\Enum\SimNao;
use App\Enum\SituacaoPlanoFinanceiro;
use App\Enum\TipoCalculoItemPlano;
use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use App\Models\PlanoFinanceiro\PlanoFinanceiro;
use App\Models\Renda\Renda;
use App\Repositories\Alocacao\AlocacaoRepository;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Repositories\Objetivo\ObjetivoRepository;
use App\Repositories\PlanoFinanceiro\PlanoFinanceiroRepository;
use App\Support\Dashboard\DashboardCache;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PlanoFinanceiroService
{
    private const PERCENTUAL_MAXIMO_DO_PLANO = 100;

    public function __construct(
        private readonly PlanoFinanceiroRepository $planoFinanceiroRepository,
        private readonly ContaBancariaRepository $contaBancariaRepository,
        private readonly ObjetivoRepository $objetivoRepository,
        private readonly AlocacaoRepository $alocacaoRepository,
        private readonly CalculadoraPlanoFinanceiro $calculadoraPlanoFinanceiro,
    ) {}

    public function listarPlanosDoUsuario(int $idUsuario): Collection
    {
        return $this->planoFinanceiroRepository->listarPlanosDoUsuario($idUsuario);
    }

    public function listarPlanosAtivosParaSelecao(int $idUsuario): Collection
    {
        return $this->planoFinanceiroRepository->listarPlanosAtivosParaSelecao($idUsuario);
    }

    public function criarPlanoFinanceiro(int $idUsuario, array $dados): PlanoFinanceiro
    {
        $itensNormalizados = $this->validarItensDoPlano($idUsuario, $dados['itens'] ?? []);
        $primeiroPlanoDoUsuario = ! $this->planoFinanceiroRepository->usuarioPossuiPlanos($idUsuario);

        $planoFinanceiro = $this->planoFinanceiroRepository->criar([
            'id_usuario' => $idUsuario,
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'situacao' => SituacaoPlanoFinanceiro::Ativo,
            'padrao' => $primeiroPlanoDoUsuario ? SimNao::Sim : SimNao::Nao,
        ]);

        $this->planoFinanceiroRepository->sincronizarItensDoPlano($planoFinanceiro, $idUsuario, $itensNormalizados);

        return $planoFinanceiro->load('itens');
    }

    public function atualizarPlanoFinanceiro(PlanoFinanceiro $planoFinanceiro, int $idUsuario, array $dados): PlanoFinanceiro
    {
        $this->garantirPropriedade($planoFinanceiro, $idUsuario);

        $itensNormalizados = $this->validarItensDoPlano($idUsuario, $dados['itens'] ?? []);
        $rendasVinculadas = $this->planoFinanceiroRepository->listarRendasVinculadas((int) $planoFinanceiro->id_plano_financeiro);

        $this->garantirItensCabemNasRendasVinculadas($itensNormalizados, $rendasVinculadas);

        $this->planoFinanceiroRepository->atualizar($planoFinanceiro, [
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
        ]);

        $this->planoFinanceiroRepository->sincronizarItensDoPlano($planoFinanceiro, $idUsuario, $itensNormalizados);

        if ($rendasVinculadas->isNotEmpty()) {
            DashboardCache::invalidar($idUsuario);
        }

        return $planoFinanceiro->load('itens');
    }

    public function alternarSituacaoPlanoFinanceiro(PlanoFinanceiro $planoFinanceiro, int $idUsuario): PlanoFinanceiro
    {
        $this->garantirPropriedade($planoFinanceiro, $idUsuario);

        if ($planoFinanceiro->estaAtivo()) {
            $this->garantirPlanoSemRendasVinculadas(
                $planoFinanceiro,
                'Este plano está aplicado em renda(s). Troque o plano dessas rendas antes de inativá-lo.',
            );
        }

        $novaSituacao = $planoFinanceiro->situacao->alternar();

        return $this->planoFinanceiroRepository->atualizar($planoFinanceiro, [
            'situacao' => $novaSituacao,
            'padrao' => $novaSituacao === SituacaoPlanoFinanceiro::Inativo ? SimNao::Nao : $planoFinanceiro->padrao,
        ]);
    }

    public function definirPlanoFinanceiroPadrao(PlanoFinanceiro $planoFinanceiro, int $idUsuario): PlanoFinanceiro
    {
        $this->garantirPropriedade($planoFinanceiro, $idUsuario);

        if (! $planoFinanceiro->estaAtivo()) {
            throw ValidationException::withMessages([
                'plano_financeiro' => 'Somente planos ativos podem ser definidos como padrão.',
            ]);
        }

        $this->planoFinanceiroRepository->removerPadraoDosDemaisPlanos($idUsuario, (int) $planoFinanceiro->id_plano_financeiro);

        return $this->planoFinanceiroRepository->atualizar($planoFinanceiro, ['padrao' => SimNao::Sim]);
    }

    public function excluirPlanoFinanceiro(PlanoFinanceiro $planoFinanceiro, int $idUsuario): void
    {
        $this->garantirPropriedade($planoFinanceiro, $idUsuario);
        $this->garantirPlanoSemRendasVinculadas(
            $planoFinanceiro,
            'Este plano está aplicado em renda(s). Troque o plano dessas rendas antes de excluí-lo.',
        );

        $this->planoFinanceiroRepository->excluir($planoFinanceiro);
    }

    public function buscarPlanoAplicavelNaRenda(int $idPlanoFinanceiro, int $idUsuario, float $valorEsperadoRenda): PlanoFinanceiro
    {
        $planoFinanceiro = $this->planoFinanceiroRepository->buscarPlanoDoUsuario($idPlanoFinanceiro, $idUsuario);

        if (! $planoFinanceiro) {
            throw ValidationException::withMessages([
                'id_plano_financeiro' => 'Plano financeiro inválido.',
            ]);
        }

        if (! $planoFinanceiro->estaAtivo()) {
            throw ValidationException::withMessages([
                'id_plano_financeiro' => 'Este plano financeiro está inativo. Ative-o ou escolha outro plano.',
            ]);
        }

        if ($this->calculadoraPlanoFinanceiro->somaSeparadaUltrapassaRenda($planoFinanceiro->itens, $valorEsperadoRenda)) {
            throw ValidationException::withMessages([
                'id_plano_financeiro' => 'Os itens que separam do saldo neste plano ultrapassam o valor esperado da renda.',
            ]);
        }

        return $planoFinanceiro;
    }

    public function garantirPropriedade(PlanoFinanceiro $planoFinanceiro, int $idUsuario): void
    {
        if ((int) $planoFinanceiro->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Este plano financeiro não pertence ao usuário autenticado.');
        }
    }

    private function validarItensDoPlano(int $idUsuario, array $itens): array
    {
        if ($itens === []) {
            throw ValidationException::withMessages([
                'itens' => 'Adicione pelo menos uma categoria ao plano.',
            ]);
        }

        $contasValidadas = [];
        $objetivosValidados = [];
        $alocacoesValidadas = [];
        $itensNormalizados = [];

        foreach (array_values($itens) as $indice => $dadosItem) {
            $itensNormalizados[] = $this->normalizarItemDoPlano(
                $idUsuario,
                $dadosItem,
                $indice,
                $contasValidadas,
                $objetivosValidados,
                $alocacoesValidadas,
            );
        }

        $this->garantirPercentualTotalDentroDoLimite($itensNormalizados);

        return $itensNormalizados;
    }

    private function normalizarItemDoPlano(
        int $idUsuario,
        array $dadosItem,
        int $indice,
        array &$contasValidadas,
        array &$objetivosValidados,
        array &$alocacoesValidadas,
    ): array {
        $prefixoErro = "itens.{$indice}";
        $tipoCalculo = TipoCalculoItemPlano::from($dadosItem['tipo_calculo']);
        $valor = round((float) $dadosItem['valor'], 2);

        if ($tipoCalculo === TipoCalculoItemPlano::Percentual && ($valor < 0.01 || $valor > self::PERCENTUAL_MAXIMO_DO_PLANO)) {
            throw ValidationException::withMessages([
                "{$prefixoErro}.valor" => 'O percentual deve estar entre 0,01% e 100%.',
            ]);
        }

        $separarDoSaldo = filter_var($dadosItem['separar_do_saldo'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $idContaBancariaDestino = null;
        $idObjetivo = null;
        $idAlocacao = null;

        if ($separarDoSaldo) {
            $idContaBancariaDestino = (int) ($dadosItem['id_conta_bancaria_destino'] ?? 0);
            $this->garantirContaDestinoValida($idUsuario, $idContaBancariaDestino, $prefixoErro, $contasValidadas);

            $idAlocacao = (int) ($dadosItem['id_alocacao'] ?? 0);
            $this->garantirAlocacaoDoUsuario($idUsuario, $idAlocacao, $prefixoErro, $alocacoesValidadas);

            $idObjetivo = ! empty($dadosItem['id_objetivo']) ? (int) $dadosItem['id_objetivo'] : null;
            if ($idObjetivo !== null) {
                $this->garantirObjetivoDoUsuario($idUsuario, $idObjetivo, $prefixoErro, $objetivosValidados);
            }
        }

        return [
            'id_item_plano' => ! empty($dadosItem['id']) ? (int) $dadosItem['id'] : null,
            'nome' => $dadosItem['nome'],
            'descricao' => $dadosItem['descricao'] ?? null,
            'cor' => CorItemPlanoFinanceiro::tryFrom((string) ($dadosItem['cor'] ?? '')) ?? CorItemPlanoFinanceiro::padrao(),
            'tipo_calculo' => $tipoCalculo,
            'valor' => $valor,
            'separar_do_saldo' => $separarDoSaldo ? SimNao::Sim : SimNao::Nao,
            'id_conta_bancaria_destino' => $idContaBancariaDestino,
            'id_objetivo' => $idObjetivo,
            'id_alocacao' => $idAlocacao,
        ];
    }

    private function garantirPercentualTotalDentroDoLimite(array $itensNormalizados): void
    {
        $percentualTotal = $this->calculadoraPlanoFinanceiro->somarPercentualDosItens(
            $this->converterParaItensSimulados($itensNormalizados),
        );

        if ($percentualTotal > self::PERCENTUAL_MAXIMO_DO_PLANO) {
            throw ValidationException::withMessages([
                'itens' => 'A soma dos percentuais do plano não pode ultrapassar 100%.',
            ]);
        }
    }

    private function garantirItensCabemNasRendasVinculadas(array $itensNormalizados, Collection $rendasVinculadas): void
    {
        if ($rendasVinculadas->isEmpty()) {
            return;
        }

        $itensSimulados = $this->converterParaItensSimulados($itensNormalizados);

        $rendaQueNaoComporta = $rendasVinculadas->first(
            fn (Renda $renda) => $this->calculadoraPlanoFinanceiro->somaSeparadaUltrapassaRenda(
                $itensSimulados,
                (float) $renda->valor_esperado,
            ),
        );

        if ($rendaQueNaoComporta) {
            throw ValidationException::withMessages([
                'itens' => "Os itens que separam do saldo ultrapassam o valor esperado da renda \"{$rendaQueNaoComporta->descricao}\".",
            ]);
        }
    }

    private function converterParaItensSimulados(array $itensNormalizados): Collection
    {
        return collect($itensNormalizados)->map(fn (array $dadosItem) => new ItemPlanoFinanceiro([
            'tipo_calculo' => $dadosItem['tipo_calculo'],
            'valor' => $dadosItem['valor'],
            'separar_do_saldo' => $dadosItem['separar_do_saldo'],
        ]));
    }

    private function garantirPlanoSemRendasVinculadas(PlanoFinanceiro $planoFinanceiro, string $mensagem): void
    {
        if ($this->planoFinanceiroRepository->contarRendasVinculadas((int) $planoFinanceiro->id_plano_financeiro) > 0) {
            throw ValidationException::withMessages([
                'plano_financeiro' => $mensagem,
            ]);
        }
    }

    private function garantirContaDestinoValida(int $idUsuario, int $idContaBancaria, string $prefixoErro, array &$contasValidadas): void
    {
        if (isset($contasValidadas[$idContaBancaria])) {
            return;
        }

        $conta = $idContaBancaria > 0
            ? $this->contaBancariaRepository->buscarParaUsuario($idContaBancaria, $idUsuario)
            : null;

        if (! $conta) {
            throw ValidationException::withMessages([
                "{$prefixoErro}.id_conta_bancaria_destino" => 'Selecione a conta onde o valor será guardado.',
            ]);
        }

        if ($conta->arquivada === SimNao::Sim) {
            throw ValidationException::withMessages([
                "{$prefixoErro}.id_conta_bancaria_destino" => 'Não é possível guardar valores em uma conta arquivada.',
            ]);
        }

        $contasValidadas[$idContaBancaria] = true;
    }

    private function garantirObjetivoDoUsuario(int $idUsuario, int $idObjetivo, string $prefixoErro, array &$objetivosValidados): void
    {
        if (isset($objetivosValidados[$idObjetivo])) {
            return;
        }

        if (! $this->objetivoRepository->buscarParaUsuario($idObjetivo, $idUsuario)) {
            throw ValidationException::withMessages([
                "{$prefixoErro}.id_objetivo" => 'Objetivo inválido.',
            ]);
        }

        $objetivosValidados[$idObjetivo] = true;
    }

    private function garantirAlocacaoDoUsuario(int $idUsuario, int $idAlocacao, string $prefixoErro, array &$alocacoesValidadas): void
    {
        if (isset($alocacoesValidadas[$idAlocacao])) {
            return;
        }

        $alocacao = $idAlocacao > 0
            ? $this->alocacaoRepository->buscarAlocacaoDoUsuario($idAlocacao, $idUsuario)
            : null;

        if (! $alocacao) {
            throw ValidationException::withMessages([
                "{$prefixoErro}.id_alocacao" => 'Selecione a alocação onde o valor será guardado.',
            ]);
        }

        $alocacoesValidadas[$idAlocacao] = true;
    }
}
