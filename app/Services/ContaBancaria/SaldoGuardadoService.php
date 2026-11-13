<?php

namespace App\Services\ContaBancaria;

use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoAporteObjetivo;
use App\Enum\TipoMovimentacaoSaldoGuardado;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\ContaBancaria\MovimentacaoSaldoGuardado;
use App\Models\Lancamento\Lancamento;
use App\Models\Objetivo\AporteObjetivo;
use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use App\Repositories\ContaBancaria\MovimentacaoSaldoGuardadoRepository;
use App\Repositories\Objetivo\AporteObjetivoRepository;
use App\Repositories\PlanoFinanceiro\PlanoFinanceiroRepository;
use App\Services\Alocacao\ConsolidadorSaldoAlocacao;
use App\Services\PlanoFinanceiro\CalculadoraPlanoFinanceiro;
use App\Support\Dashboard\DashboardCache;
use Illuminate\Support\Collection;

class SaldoGuardadoService
{
    private const LIMITE_MOVIMENTACOES_RECENTES = 10;

    public function __construct(
        private readonly MovimentacaoSaldoGuardadoRepository $movimentacaoSaldoGuardadoRepository,
        private readonly PlanoFinanceiroRepository $planoFinanceiroRepository,
        private readonly AporteObjetivoRepository $aporteObjetivoRepository,
        private readonly CalculadoraPlanoFinanceiro $calculadoraPlanoFinanceiro,
        private readonly ConsolidadorSaldoAlocacao $consolidadorSaldoAlocacao,
    ) {}

    public function separarItensPlanoDoRecebimentoRenda(Lancamento $lancamentoRenda): Collection
    {
        if (! $lancamentoRenda->ehRenda() || $lancamentoRenda->situacao !== SituacaoLancamento::Recebido) {
            return collect();
        }

        $itensQueSeparam = $this->planoFinanceiroRepository
            ->buscarItensQueSeparamDasRendas([(int) $lancamentoRenda->id_renda])
            ->get($lancamentoRenda->id_renda, collect());

        if ($itensQueSeparam->isEmpty()) {
            return collect();
        }

        $valoresPorItem = $this->calculadoraPlanoFinanceiro->distribuirValoresSeparados(
            $itensQueSeparam,
            (float) $lancamentoRenda->valor,
        );

        $idsItensJaSeparados = $this->movimentacaoSaldoGuardadoRepository
            ->listarIdsItensPlanoSeparadosDoLancamento((int) $lancamentoRenda->id_lancamento);
        $nomePlano = $this->planoFinanceiroRepository->buscarNomeDoPlano((int) $itensQueSeparam->first()->id_plano_financeiro);

        $movimentacoes = collect();

        foreach ($itensQueSeparam as $itemPlanoFinanceiro) {
            $idItem = (int) $itemPlanoFinanceiro->id_item_plano;
            $valorSeparado = $valoresPorItem[$idItem] ?? 0.0;

            if ($valorSeparado <= 0 || in_array($idItem, $idsItensJaSeparados, true)) {
                continue;
            }

            $movimentacoes->push($this->registrarSeparacao($lancamentoRenda, $itemPlanoFinanceiro, $nomePlano, $valorSeparado));
        }

        if ($movimentacoes->isNotEmpty()) {
            $this->consolidadorSaldoAlocacao->recalcularSaldosConsolidadosDasAlocacoes(
                $movimentacoes->pluck('id_alocacao')->all(),
            );
            DashboardCache::invalidar((int) $lancamentoRenda->id_usuario);
        }

        return $movimentacoes;
    }

    public function estornarSeparacoesDoLancamento(Lancamento $lancamentoRenda): void
    {
        $separacoes = $this->movimentacaoSaldoGuardadoRepository->listarSeparacoesDoLancamento(
            (int) $lancamentoRenda->id_lancamento,
        );

        if ($separacoes->isEmpty()) {
            return;
        }

        $idsAlocacoesAfetadas = $separacoes->pluck('id_alocacao')->all();

        foreach ($separacoes as $separacao) {
            $idAporteObjetivo = $separacao->id_aporte_objetivo;

            $this->movimentacaoSaldoGuardadoRepository->excluir($separacao);

            if ($idAporteObjetivo !== null) {
                AporteObjetivo::query()
                    ->where('id_aporte_objetivo', $idAporteObjetivo)
                    ->where('tipo', TipoAporteObjetivo::DestinacaoRenda)
                    ->delete();
            }
        }

        $this->consolidadorSaldoAlocacao->recalcularSaldosConsolidadosDasAlocacoes($idsAlocacoesAfetadas);
        DashboardCache::invalidar((int) $lancamentoRenda->id_usuario);
    }

    public function listarMovimentacoesRecentesPorConta(Collection $contasBancarias): array
    {
        return $contasBancarias
            ->reject(fn (ContaBancaria $conta) => $conta->arquivada === SimNao::Sim)
            ->mapWithKeys(fn (ContaBancaria $conta) => [
                $conta->id_conta_bancaria => $this->movimentacaoSaldoGuardadoRepository->listarMovimentacoesRecentesDaConta(
                    (int) $conta->id_conta_bancaria,
                    self::LIMITE_MOVIMENTACOES_RECENTES,
                ),
            ])
            ->all();
    }

    private function registrarSeparacao(
        Lancamento $lancamentoRenda,
        ItemPlanoFinanceiro $itemPlanoFinanceiro,
        string $nomePlano,
        float $valorSeparado,
    ): MovimentacaoSaldoGuardado {
        $dataSeparacao = ($lancamentoRenda->data_pagamento ?? $lancamentoRenda->data_vencimento)->toDateString();
        $aporteObjetivo = $this->registrarAporteNoObjetivo($lancamentoRenda, $itemPlanoFinanceiro, $nomePlano, $valorSeparado, $dataSeparacao);

        return $this->movimentacaoSaldoGuardadoRepository->criar([
            'id_usuario' => $lancamentoRenda->id_usuario,
            'id_conta_bancaria' => $itemPlanoFinanceiro->id_conta_bancaria_destino,
            'id_conta_bancaria_origem' => $lancamentoRenda->id_conta_bancaria,
            'id_item_plano' => $itemPlanoFinanceiro->id_item_plano,
            'id_alocacao' => $itemPlanoFinanceiro->id_alocacao,
            'id_lancamento_origem' => $lancamentoRenda->id_lancamento,
            'id_aporte_objetivo' => $aporteObjetivo?->id_aporte_objetivo,
            'tipo' => TipoMovimentacaoSaldoGuardado::Separacao,
            'valor' => $valorSeparado,
            'data_movimentacao' => $dataSeparacao,
            'observacao' => mb_substr($itemPlanoFinanceiro->nome.' · '.$nomePlano.' · '.$lancamentoRenda->descricao, 0, 255),
        ]);
    }

    private function registrarAporteNoObjetivo(
        Lancamento $lancamentoRenda,
        ItemPlanoFinanceiro $itemPlanoFinanceiro,
        string $nomePlano,
        float $valorSeparado,
        string $dataSeparacao,
    ): ?AporteObjetivo {
        if ($itemPlanoFinanceiro->id_objetivo === null) {
            return null;
        }

        return $this->aporteObjetivoRepository->criar([
            'id_objetivo' => $itemPlanoFinanceiro->id_objetivo,
            'id_usuario' => $lancamentoRenda->id_usuario,
            'tipo' => TipoAporteObjetivo::DestinacaoRenda,
            'valor' => $valorSeparado,
            'data_aporte' => $dataSeparacao,
            'id_conta_bancaria' => $itemPlanoFinanceiro->id_conta_bancaria_destino,
            'id_lancamento' => null,
            'observacao' => mb_substr('Plano '.$nomePlano.': '.$itemPlanoFinanceiro->nome, 0, 255),
        ]);
    }
}
