<?php

namespace App\Enum;

enum TipoLancamento: string
{
    case Receita = 'receita';
    case Despesa = 'despesa';

    public function rotulo(): string
    {
        return match ($this) {
            self::Receita => 'Receita',
            self::Despesa => 'Despesa',
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
