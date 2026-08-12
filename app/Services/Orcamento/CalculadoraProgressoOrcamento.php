<?php

namespace App\Services\Orcamento;

class CalculadoraProgressoOrcamento
{
    public function montarResumo(float $valorMensal, float $gastoMes): array
    {
        $valorMensal = round(max(0, $valorMensal), 2);
        $gastoMes = round(max(0, $gastoMes), 2);
        $valorRestante = round(max(0, $valorMensal - $gastoMes), 2);
        $valorExcedente = round(max(0, $gastoMes - $valorMensal), 2);
        $ultrapassado = $gastoMes > $valorMensal && $valorMensal > 0;

        $percentual = $valorMensal > 0
            ? round(($gastoMes / $valorMensal) * 100, 1)
            : 0.0;

        return [
            'gasto_mes' => $gastoMes,
            'valor_restante' => $valorRestante,
            'valor_excedente' => $valorExcedente,
            'percentual' => (float) $percentual,
            'percentual_barra' => (float) min(100, $percentual),
            'ultrapassado' => $ultrapassado,
        ];
    }
}
