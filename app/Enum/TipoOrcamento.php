<?php

namespace App\Enum;

enum TipoOrcamento: string
{
    case PorCategoria = 'por_categoria';
    case PorServico = 'por_servico';

    public function rotulo(): string
    {
        return match ($this) {
            self::PorCategoria => 'Por categoria',
            self::PorServico => 'Por serviço',
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
