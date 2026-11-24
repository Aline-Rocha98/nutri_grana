<?php

namespace App\Enum;

enum StatusConviteCompartilhamento: string
{
    case Pendente = 'pendente';
    case Aceito = 'aceito';
    case Recusado = 'recusado';
    case Expirado = 'expirado';
    case Revogado = 'revogado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Aceito => 'Aceito',
            self::Recusado => 'Recusado',
            self::Expirado => 'Expirado',
            self::Revogado => 'Revogado',
        };
    }
}
