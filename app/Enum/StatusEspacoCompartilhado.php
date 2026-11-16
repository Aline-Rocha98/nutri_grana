<?php

namespace App\Enum;

enum StatusEspacoCompartilhado: string
{
    case Ativo = 'ativo';
    case Arquivado = 'arquivado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Arquivado => 'Arquivado',
        };
    }
}
