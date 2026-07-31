<?php

namespace App\Support\Data;

final class Valor
{
    public static function normalizarValorMonetario(mixed $valor): mixed
    {
        if (! is_string($valor)) {
            return $valor;
        }

        $normalizado = str_replace(['R$', ' ', '.'], '', $valor);
        $normalizado = str_replace(',', '.', $normalizado);
        
        return $normalizado === '' ? $valor : $normalizado;
    }
}