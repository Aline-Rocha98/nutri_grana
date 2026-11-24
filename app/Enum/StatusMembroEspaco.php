<?php

namespace App\Enum;

enum StatusMembroEspaco: string
{
    case Ativo = 'ativo';
    case Removido = 'removido';
    case Saiu = 'saiu';

    public function rotulo(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Removido => 'Removido',
            self::Saiu => 'Saiu do espaço',
        };
    }
}
