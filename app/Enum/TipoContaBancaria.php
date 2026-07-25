<?php

namespace App\Enum;

enum TipoContaBancaria: string
{
    case Corrente = 'corrente';
    case Poupanca = 'poupanca';
    case Investimento = 'investimento';
    
    public function rotulo(): string
    {
        return match ($this) {
            self::Corrente => 'Corrente',
            self::Poupanca => 'Poupança',
            self::Investimento => 'Investimento',
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
