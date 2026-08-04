<?php

namespace App\Enum;

enum TipoCategoria: string
{
    case Entrada = 'entrada';
    case Saida = 'saida';

    public function rotulo(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Saida => 'Saída',
        };
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $tipoCategoria) => [
                'valor' => $tipoCategoria->value,
                'rotulo' => $tipoCategoria->rotulo(),
            ],
            self::cases()
        );
    }
}