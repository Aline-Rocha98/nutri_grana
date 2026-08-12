<?php

namespace App\Services\Objetivo;

use App\Enum\SituacaoRitmoObjetivo;
use Carbon\Carbon;

class CalculadoraProgressoObjetivo
{
    public function montarResumo(float $valorMeta, float $valorGuardado, Carbon $dataInicio, Carbon $dataLimite, ?Carbon $hoje = null): array 
    {
        $hoje = ($hoje ?? Carbon::today())->startOfDay();
        $dataInicio = $dataInicio->copy()->startOfDay();
        $dataLimite = $dataLimite->copy()->startOfDay();

        $valorGuardado = round(max(0, $valorGuardado), 2);
        $valorMeta = round(max(0, $valorMeta), 2);
        $valorFaltante = round(max(0, $valorMeta - $valorGuardado), 2);

        $percentualAtual = $valorMeta > 0
            ? (float) min(100, round(($valorGuardado / $valorMeta) * 100, 1))
            : 0.0;

        $mesesRestantes = $this->calcularMesesRestantes($hoje, $dataLimite);
        $depositoMensalSugerido = $valorFaltante > 0
            ? round($valorFaltante / $mesesRestantes, 2)
            : 0.0;

        $diasTotais = max(1, $dataInicio->diffInDays($dataLimite));
        $diasDecorridos = $hoje->lt($dataInicio)
            ? 0
            : min($diasTotais, $dataInicio->diffInDays($hoje));

        $valorEsperadoHoje = round($valorMeta * ($diasDecorridos / $diasTotais), 2);
        $diferencaRitmo = round($valorGuardado - $valorEsperadoHoje, 2);
        $situacaoRitmo = $this->definirSituacaoRitmo($valorMeta, $valorGuardado, $valorEsperadoHoje, $hoje, $dataLimite);

        return [
            'valor_guardado' => $valorGuardado,
            'valor_faltante' => $valorFaltante,
            'percentual_atual' => $percentualAtual,
            'deposito_mensal_sugerido' => $depositoMensalSugerido,
            'meses_restantes' => $mesesRestantes,
            'valor_esperado_hoje' => $valorEsperadoHoje,
            'diferenca_ritmo' => $diferencaRitmo,
            'situacao_ritmo' => $situacaoRitmo->value,
            'situacao_ritmo_rotulo' => $situacaoRitmo->rotulo(),
        ];
    }

    private function calcularMesesRestantes(Carbon $hoje, Carbon $dataLimite): int
    {
        if ($hoje->gte($dataLimite)) {
            return 1;
        }

        $meses = (int) $hoje->diffInMonths($dataLimite);

        return max(1, $meses);
    }

    private function definirSituacaoRitmo(float $valorMeta, float $valorGuardado, float $valorEsperadoHoje, Carbon $hoje, Carbon $dataLimite): SituacaoRitmoObjetivo 
    {
        if ($valorGuardado >= $valorMeta && $valorMeta > 0) {
            return SituacaoRitmoObjetivo::Concluido;
        }

        if ($hoje->gt($dataLimite) && $valorGuardado < $valorMeta) {
            return SituacaoRitmoObjetivo::Vencido;
        }

        $tolerancia = 0.01;
        $diferenca = $valorGuardado - $valorEsperadoHoje;

        if (abs($diferenca) <= $tolerancia) {
            return SituacaoRitmoObjetivo::EmDia;
        }

        return $diferenca > 0
            ? SituacaoRitmoObjetivo::Adiantado
            : SituacaoRitmoObjetivo::Atrasado;
    }
}
