<?php

namespace App\Enum;

enum StatusLancamentoCompartilhado: string
{
    case Pendente = 'pendente';
    case Pago = 'pago';
    case Cancelado = 'cancelado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Pago => 'Pago',
            self::Cancelado => 'Cancelado',
        };
    }
}
