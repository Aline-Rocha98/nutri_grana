<?php

namespace App\Enum;

enum SimNao: string
{
    case Sim = 'S';
    case Nao = 'N';

    public static function fromToggle(mixed $valor): ?self
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor === true || $valor === 1 || $valor === '1' || $valor === 'S') {
            return self::Sim;
        }

        if ($valor === false || $valor === 0 || $valor === '0' || $valor === 'N') {
            return self::Nao;
        }

        return self::tryFrom((string) $valor);
    }

    public function estaAtivo(): bool
    {
        return $this === self::Sim;
    }
}
