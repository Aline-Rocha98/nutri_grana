<?php

namespace App\Services\Renda;

use App\Models\Lancamento\Lancamento;
use App\Repositories\ContaBancaria\MovimentacaoSaldoGuardadoRepository;
use App\Repositories\Lancamento\LancamentoRepository;
use App\Repositories\PlanoFinanceiro\PlanoFinanceiroRepository;
use App\Services\PlanoFinanceiro\CalculadoraPlanoFinanceiro;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PrevisorPlanoFinanceiroRenda
{
    public function __construct(
        private readonly LancamentoRepository $lancamentoRepository,
        private readonly PlanoFinanceiroRepository $planoFinanceiroRepository,
        private readonly MovimentacaoSaldoGuardadoRepository $movimentacaoSaldoGuardadoRepository,
        private readonly CalculadoraPlanoFinanceiro $calculadoraPlanoFinanceiro,
    ) {}

    public function calcularSeparacoesPrevistasNoPeriodo(int $idUsuario, Carbon $inicio, Carbon $fim): float
    {
        if ($inicio->gt($fim)) {
            return 0.0;
        }

        $rendasPrevistas = $this->lancamentoRepository->listarRendasPrevistasEmContasNoPeriodo($idUsuario, $inicio, $fim);

        return $this->somarSeparacoesDasRendas($rendasPrevistas);
    }

    public function calcularSeparacoesPrevistasAtrasadas(int $idUsuario, Carbon $antesDe): float
    {
        $rendasPrevistas = $this->lancamentoRepository->listarRendasPrevistasEmContasNoPeriodo(
            $idUsuario,
            null,
            $antesDe->copy()->subDay(),
        );

        return $this->somarSeparacoesDasRendas($rendasPrevistas);
    }

    public function calcularSeparadoNoMes(int $idUsuario, int $ano, int $mes): float
    {
        $inicio = Carbon::create($ano, $mes, 1)->startOfDay();
        $fim = $inicio->copy()->endOfMonth()->startOfDay();

        $separado = $this->movimentacaoSaldoGuardadoRepository->somarSeparacoesNoPeriodo(
            $idUsuario,
            $inicio->toDateString(),
            $fim->toDateString(),
        );

        return round($separado + $this->calcularSeparacoesPrevistasNoPeriodo($idUsuario, $inicio, $fim), 2);
    }

    private function somarSeparacoesDasRendas(Collection $rendasPrevistas): float
    {
        if ($rendasPrevistas->isEmpty()) {
            return 0.0;
        }

        $itensPorRenda = $this->planoFinanceiroRepository->buscarItensQueSeparamDasRendas(
            $rendasPrevistas->pluck('id_renda')->unique()->values()->all(),
        );

        $total = $rendasPrevistas->sum(function (Lancamento $lancamentoRenda) use ($itensPorRenda) {
            $itensDoPlano = $itensPorRenda->get($lancamentoRenda->id_renda, collect());

            return $this->calculadoraPlanoFinanceiro->somarValoresSeparados(
                $itensDoPlano,
                (float) $lancamentoRenda->valor,
            );
        });

        return round($total, 2);
    }
}
