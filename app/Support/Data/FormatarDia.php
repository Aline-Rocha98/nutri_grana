<?php

namespace App\Support\Data;

final class FormatarDia
{
    public static function pad(int|string|null $dia): string
    {
        if ($dia === null || $dia === '') {
            return '';
        }

        $numero = (int) $dia;

        if ($numero < 1 || $numero > 31) {
            return '';
        }

        return str_pad((string) $numero, 2, '0', STR_PAD_LEFT);
    }
}
