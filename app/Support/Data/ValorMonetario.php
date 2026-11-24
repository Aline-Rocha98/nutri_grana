<?php

namespace App\Support\Data;

final class ValorMonetario
{
    public static function converterParaCentavos(string|int|float|null $valor): int
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        if (is_int($valor)) {
            return $valor * 100;
        }

        if (is_float($valor)) {
            return (int) round($valor * 100);
        }

        $texto = trim($valor);
        $negativo = str_starts_with($texto, '-');
        $texto = ltrim($texto, '-+');

        [$inteiros, $decimais] = array_pad(explode('.', $texto, 2), 2, '');
        $decimais = substr(str_pad($decimais, 2, '0'), 0, 2);
        $centavos = ((int) $inteiros) * 100 + (int) $decimais;

        return $negativo ? -$centavos : $centavos;
    }

    public static function converterCentavosParaDecimal(int $centavos): string
    {
        $sinal = $centavos < 0 ? '-' : '';
        $absoluto = abs($centavos);

        return sprintf('%s%d.%02d', $sinal, intdiv($absoluto, 100), $absoluto % 100);
    }

    public static function formatarCentavosEmReais(int $centavos): string
    {
        $sinal = $centavos < 0 ? '-' : '';
        $absoluto = abs($centavos);

        return $sinal.number_format(intdiv($absoluto, 100), 0, ',', '.').','.sprintf('%02d', $absoluto % 100);
    }
}
