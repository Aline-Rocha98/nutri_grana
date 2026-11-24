<?php

namespace App\Support\Data;

final class IniciaisNome
{
    public static function extrairIniciais(?string $nome, string $padrao = 'NG'): string
    {
        $iniciais = collect(explode(' ', $nome ?? ''))
            ->filter()
            ->take(2)
            ->map(fn (string $parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');

        return $iniciais ?: $padrao;
    }
}
