<?php

namespace App\Enum;

enum PeriodoDashboard: string
{
    case Atual = 'atual';
    case Anterior = 'anterior';
    case TresMeses = '3meses';

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
