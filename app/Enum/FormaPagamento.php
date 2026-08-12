<?php

namespace App\Enum;

enum FormaPagamento: string
{
    case ContaBancaria = 'conta_bancaria';
    case CartaoCredito = 'cartao_credito';

    public function rotulo(): string
    {
        return match ($this) {
            self::ContaBancaria => 'Conta bancária',
            self::CartaoCredito => 'Cartão de crédito',
        };
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $forma) => [
                'valor' => $forma->value,
                'rotulo' => $forma->rotulo(),
            ],
            self::cases()
        );
    }
}
