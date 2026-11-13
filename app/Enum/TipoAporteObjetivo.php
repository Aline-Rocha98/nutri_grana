<?php

namespace App\Enum;

enum TipoAporteObjetivo: string
{
    case Manual = 'manual';
    case ContaBancaria = 'conta_bancaria';
    case DestinacaoRenda = 'destinacao_renda';

    public function rotulo(): string
    {
        return match ($this) {
            self::Manual => 'Aporte manual',
            self::ContaBancaria => 'Retirada de conta bancária',
            self::DestinacaoRenda => 'Plano financeiro da renda',
        };
    }
}
