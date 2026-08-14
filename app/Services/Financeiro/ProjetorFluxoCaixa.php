<?php

namespace App\Services\Financeiro;

use App\Enum\SimNao;
use App\Models\ContaBancaria\ContaBancaria;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Repositories\Lancamento\LancamentoRepository;
use App\Services\Lancamento\RecorrenciaService;
use Carbon\Carbon;

/**
 * Projeta o fluxo de caixa com base no saldo atual das contas
 * e nos lançamentos pendentes previstos (sem criar compromisso novo).
 */
class ProjetorFluxoCaixa
{
    public const HORIZONTE_MAXIMO_MESES = 12;

    public function __construct(
        private readonly ContaBancariaRepository $contaBancariaRepository,
        private readonly LancamentoRepository $lancamentoRepository,
        private readonly RecorrenciaService $recorrenciaService,
    ) {}

    /**
     * @return array{
     *     saldo_atual_contas: float,
     *     receitas_previstas: float,
     *     despesas_previstas: float,
     *     saldo_disponivel_planejamento: float,
     *     meses: list<array{
     *         ano: int,
     *         mes: int,
     *         rotulo: string,
     *         receitas: float,
     *         despesas: float,
     *         saldo_projetado: float
     *     }>,
     *     saldo_projetado_final: float,
     *     horizonte_ate: string
     * }
     */
    public function projetar(
        int $idUsuario,
        ?Carbon $ate = null,
        ?float $compromissoExtra = null,
        ?Carbon $dataCompromisso = null,
        ?Carbon $referencia = null,
    ): array {
        $referencia = ($referencia ?? Carbon::today())->copy()->startOfDay();
        $ate = $this->resolverHorizonte($referencia, $ate);
        $dataCompromisso = $dataCompromisso?->copy()->startOfDay();
        $compromissoExtra = $compromissoExtra !== null ? round(max(0, $compromissoExtra), 2) : null;

        $this->materializarRecorrenciasAte($idUsuario, $ate);

        $saldoAtual = $this->somarSaldoAtualContasAtivas($idUsuario);
        $atrasadas = $this->lancamentoRepository->somarPendenciasAtrasadasEmContas($idUsuario, $referencia);

        $saldoCursor = round(
            $saldoAtual + $atrasadas['receitas'] - $atrasadas['despesas'],
            2
        );

        $receitasPrevistas = $atrasadas['receitas'];
        $despesasPrevistas = $atrasadas['despesas'];
        $meses = [];

        $cursorMes = $referencia->copy()->startOfMonth();
        $fimMesLimite = $ate->copy()->startOfMonth();

        while ($cursorMes->lte($fimMesLimite)) {
            $ano = (int) $cursorMes->year;
            $mes = (int) $cursorMes->month;
            [$receitasMes, $despesasMes] = $this->pendenciasDoMesSemAtrasadasJaContadas(
                $idUsuario,
                $cursorMes,
                $referencia,
            );

            $saldoCursor = round($saldoCursor + $receitasMes - $despesasMes, 2);

            $despesasExibidas = $despesasMes;
            if (
                $compromissoExtra
                && $dataCompromisso
                && $dataCompromisso->year === $ano
                && $dataCompromisso->month === $mes
            ) {
                $saldoCursor = round($saldoCursor - $compromissoExtra, 2);
                $despesasExibidas = round($despesasMes + $compromissoExtra, 2);
            }

            $receitasPrevistas = round($receitasPrevistas + $receitasMes, 2);
            $despesasPrevistas = round($despesasPrevistas + $despesasMes, 2);

            $meses[] = [
                'ano' => $ano,
                'mes' => $mes,
                'rotulo' => ucfirst($cursorMes->copy()->locale('pt_BR')->translatedFormat('F/Y')),
                'receitas' => $receitasMes,
                'despesas' => $despesasExibidas,
                'saldo_projetado' => $saldoCursor,
            ];

            $cursorMes->addMonthNoOverflow();
        }

        return [
            'saldo_atual_contas' => $saldoAtual,
            'receitas_previstas' => $receitasPrevistas,
            'despesas_previstas' => $despesasPrevistas,
            'saldo_disponivel_planejamento' => round(
                $saldoAtual + $receitasPrevistas - $despesasPrevistas,
                2
            ),
            'meses' => $meses,
            'saldo_projetado_final' => $saldoCursor,
            'horizonte_ate' => $ate->toDateString(),
        ];
    }

    public function resolverHorizonte(Carbon $referencia, ?Carbon $ate): Carbon
    {
        $limiteMaximo = $referencia->copy()
            ->addMonthsNoOverflow(self::HORIZONTE_MAXIMO_MESES)
            ->endOfMonth()
            ->startOfDay();

        if ($ate === null) {
            return $limiteMaximo;
        }

        $ate = $ate->copy()->startOfDay();

        if ($ate->lt($referencia)) {
            return $referencia->copy()->endOfMonth()->startOfDay();
        }

        return $ate->lte($limiteMaximo) ? $ate : $limiteMaximo;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function pendenciasDoMesSemAtrasadasJaContadas(
        int $idUsuario,
        Carbon $cursorMes,
        Carbon $referencia,
    ): array {
        $ano = (int) $cursorMes->year;
        $mes = (int) $cursorMes->month;
        $pendenciasMes = $this->lancamentoRepository->somarPendenciasEmContasNoMes($idUsuario, $ano, $mes);
        $receitasMes = $pendenciasMes['receitas'];
        $despesasMes = $pendenciasMes['despesas'];

        if (! $cursorMes->isSameMonth($referencia)) {
            return [$receitasMes, $despesasMes];
        }

        $inicioMes = $cursorMes->copy()->startOfMonth();
        if ($referencia->lte($inicioMes)) {
            return [$receitasMes, $despesasMes];
        }

        $atrasadasNoMes = $this->lancamentoRepository->somarPendenciasEmContasNoPeriodo(
            $idUsuario,
            $inicioMes,
            $referencia->copy()->subDay(),
        );

        return [
            round(max(0, $receitasMes - $atrasadasNoMes['receitas']), 2),
            round(max(0, $despesasMes - $atrasadasNoMes['despesas']), 2),
        ];
    }

    private function materializarRecorrenciasAte(int $idUsuario, Carbon $ate): void
    {
        $cursor = Carbon::today()->startOfMonth();
        $fim = $ate->copy()->startOfMonth();

        while ($cursor->lte($fim)) {
            $this->recorrenciaService->gerarParaMes(
                $idUsuario,
                (int) $cursor->year,
                (int) $cursor->month,
            );
            $cursor->addMonthNoOverflow();
        }
    }

    private function somarSaldoAtualContasAtivas(int $idUsuario): float
    {
        $contas = ContaBancaria::query()
            ->where('id_usuario', $idUsuario)
            ->where('arquivada', SimNao::Nao)
            ->get();

        $total = 0.0;

        foreach ($contas as $conta) {
            $movimentado = $this->contaBancariaRepository->saldoMovimentado((int) $conta->id_conta_bancaria);
            $total += (float) $conta->saldo_inicial + $movimentado;
        }

        return round($total, 2);
    }
}
