<?php

namespace App\Enum;

enum PapelMembroEspaco: string
{
    case Administrador = 'administrador';
    case Membro = 'membro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Membro => 'Membro',
        };
    }
}
