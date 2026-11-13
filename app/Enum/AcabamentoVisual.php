<?php

namespace App\Enum;

enum AcabamentoVisual: string
{
    case Esmeralda = 'esmeralda';
    case Grafite = 'grafite';
    case Oceano = 'oceano';
    case Prata = 'prata';
    case Roxo = 'roxo';
    case Laranja = 'laranja';

    public static function padrao(): self
    {
        return self::Esmeralda;
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Esmeralda => 'Esmeralda',
            self::Grafite => 'Grafite',
            self::Oceano => 'Oceano',
            self::Prata => 'Prata',
            self::Roxo => 'Roxo',
            self::Laranja => 'Laranja',
        };
    }

    public function corDestaque(): string
    {
        return match ($this) {
            self::Esmeralda => '#1fa67e',
            self::Grafite => '#3f3f46',
            self::Oceano => '#0e7490',
            self::Prata => '#9ca3af',
            self::Roxo => '#7c3aed',
            self::Laranja => '#ea580c',
        };
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $acabamento) => [
                'valor' => $acabamento->value,
                'rotulo' => $acabamento->rotulo(),
                'cor' => $acabamento->corDestaque(),
            ],
            self::cases()
        );
    }
}
