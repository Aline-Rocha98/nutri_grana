<?php

namespace App\Enum;

enum SituacaoFatura: string
{
    case Aberta = 'aberta';
    case Fechada = 'fechada';
    case Paga = 'paga';

    public function rotulo(): string
    {
        return match ($this) {
            self::Aberta => 'Aberta',
            self::Fechada => 'Fechada',
            self::Paga => 'Paga',
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
