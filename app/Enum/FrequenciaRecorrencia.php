<?php

namespace App\Enum;

use Carbon\CarbonInterface;

enum FrequenciaRecorrencia: string
{
    case Mensal = 'mensal';
    case Semanal = 'semanal';
    case Anual = 'anual';

    public function rotulo(): string
    {
        return match ($this) {
            self::Mensal => 'Todo mês',
            self::Semanal => 'Toda semana',
            self::Anual => 'Todo ano',
        };
    }

    public function calcularDataDaOcorrencia(CarbonInterface $dataInicio, int $indiceOcorrencia): CarbonInterface
    {
        return match ($this) {
            self::Mensal => $dataInicio->copy()->addMonthsNoOverflow($indiceOcorrencia),
            self::Semanal => $dataInicio->copy()->addWeeks($indiceOcorrencia),
            self::Anual => $dataInicio->copy()->addYearsNoOverflow($indiceOcorrencia),
        };
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $frequencia) => [
                'valor' => $frequencia->value,
                'rotulo' => $frequencia->rotulo(),
            ],
            self::cases()
        );
    }
}
