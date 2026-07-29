<?php

namespace App\Enum;

enum BandeiraCartaoCredito: string
{
    case Selecione = 'selecione';
    case Visa = 'visa';
    case Mastercard = 'mastercard';
    case Elo = 'elo';
    case Amex = 'amex';
    case Hipercard = 'hipercard';
    case Outra = 'outra';

    public function rotulo(): string
    {
        return match ($this) {
            self::Selecione => 'Selecione',
            self::Visa => 'Visa',
            self::Mastercard => 'Mastercard',
            self::Elo => 'Elo',
            self::Amex => 'American Express',
            self::Hipercard => 'Hipercard',
            self::Outra => 'Outra',
        };
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $bandeira) => [
                'valor' => $bandeira->value,
                'rotulo' => $bandeira->rotulo(),
            ],
            self::cases()
        );
    }
}
