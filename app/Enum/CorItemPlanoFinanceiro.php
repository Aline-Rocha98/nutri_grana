<?php

namespace App\Enum;

enum CorItemPlanoFinanceiro: string
{
    case Verde = 'verde';
    case Azul = 'azul';
    case Roxo = 'roxo';
    case Laranja = 'laranja';
    case Rosa = 'rosa';
    case Ciano = 'ciano';

    public static function padrao(): self
    {
        return self::Verde;
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Verde => 'Verde',
            self::Azul => 'Azul',
            self::Roxo => 'Roxo',
            self::Laranja => 'Laranja',
            self::Rosa => 'Rosa',
            self::Ciano => 'Ciano',
        };
    }

    public function corHexadecimal(): string
    {
        return match ($this) {
            self::Verde => '#10b981',
            self::Azul => '#38bdf8',
            self::Roxo => '#a855f7',
            self::Laranja => '#f59e0b',
            self::Rosa => '#ec4899',
            self::Ciano => '#14b8a6',
        };
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $cor) => [
                'valor' => $cor->value,
                'rotulo' => $cor->rotulo(),
                'cor' => $cor->corHexadecimal(),
            ],
            self::cases()
        );
    }
}
