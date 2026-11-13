<?php

namespace App\Services\Alocacao;

use App\Repositories\Alocacao\AlocacaoRepository;
use App\Repositories\ContaBancaria\MovimentacaoSaldoGuardadoRepository;

class ConsolidadorSaldoAlocacao
{
    public function __construct(
        private readonly MovimentacaoSaldoGuardadoRepository $movimentacaoSaldoGuardadoRepository,
        private readonly AlocacaoRepository $alocacaoRepository,
    ) {}

    public function recalcularSaldoConsolidadoDaAlocacao(int $idAlocacao): float
    {
        $saldoCalculado = $this->movimentacaoSaldoGuardadoRepository->somarSaldoDaAlocacao($idAlocacao);

        $this->alocacaoRepository->atualizarSaldoConsolidado($idAlocacao, $saldoCalculado);

        return $saldoCalculado;
    }

    public function recalcularSaldosConsolidadosDasAlocacoes(array $idsAlocacoes): void
    {
        $idsUnicos = array_values(array_unique(array_filter(array_map('intval', $idsAlocacoes))));

        foreach ($idsUnicos as $idAlocacao) {
            $this->recalcularSaldoConsolidadoDaAlocacao($idAlocacao);
        }
    }

    public function calcularSaldoDaAlocacaoNaConta(int $idAlocacao, int $idContaBancaria): float
    {
        $saldosPorConta = $this->movimentacaoSaldoGuardadoRepository->somarSaldoDaAlocacaoPorConta($idAlocacao);

        return round((float) ($saldosPorConta[$idContaBancaria] ?? 0), 2);
    }

    public function calcularSaldosPorContaDasAlocacoes(array $idsAlocacoes): array
    {
        return $this->movimentacaoSaldoGuardadoRepository->somarSaldosDasAlocacoesPorConta($idsAlocacoes);
    }
}
