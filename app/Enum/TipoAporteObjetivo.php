<?php

namespace App\Enum;

enum TipoAporteObjetivo: string
{
    case Manual = 'manual';
    case ContaBancaria = 'conta_bancaria';

    public function rotulo(): string
    {
        return match ($this) {
            self::Manual => 'Aporte manual',
            self::ContaBancaria => 'Retirada de conta bancária',
        };
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $tipo) => [
                'valor' => $tipo->value,
                'rotulo' => $tipo->rotulo(),
            ],
            self::cases()
        );
    }
}
