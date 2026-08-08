<?php

namespace App\Enum;

enum SituacaoLancamento: string
{
    case Pendente = 'pendente';
    case Pago = 'pago';
    case Cancelado = 'cancelado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Pago => 'Pago',
            self::Cancelado => 'Cancelado',
        };
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $situacao) => [
                'valor' => $situacao->value,
                'rotulo' => $situacao->rotulo(),
            ],
            self::cases()
        );
    }
}
