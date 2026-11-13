<?php

namespace App\Enum;

enum TipoCalculoItemPlano: string
{
    case Percentual = 'percentual';
    case ValorFixo = 'valor_fixo';

    public function rotulo(): string
    {
        return match ($this) {
            self::Percentual => 'Percentual da renda',
            self::ValorFixo => 'Valor fixo',
        };
    }

    public function rotuloCurto(): string
    {
        return match ($this) {
            self::Percentual => '%',
            self::ValorFixo => 'R$',
        };
    }

    public function calcularValorDoItem(float $valorBase, float $valorConfigurado): float
    {
        if ($valorBase <= 0 || $valorConfigurado <= 0) {
            return 0.0;
        }

        $valorCalculado = match ($this) {
            self::Percentual => $valorBase * min($valorConfigurado, 100) / 100,
            self::ValorFixo => min($valorConfigurado, $valorBase),
        };

        return round($valorCalculado, 2);
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $tipo) => [
                'valor' => $tipo->value,
                'rotulo' => $tipo->rotuloCurto(),
                'descricao' => $tipo->rotulo(),
            ],
            self::cases()
        );
    }
}
