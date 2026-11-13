<?php

namespace App\Enum;

enum TipoMovimentacaoSaldoGuardado: string
{
    case Separacao = 'separacao';
    case Resgate = 'resgate';
    case Transferencia = 'transferencia';

    public function rotulo(): string
    {
        return match ($this) {
            self::Separacao => 'Valor guardado',
            self::Resgate => 'Resgate',
            self::Transferencia => 'Transferência entre alocações',
        };
    }
}
