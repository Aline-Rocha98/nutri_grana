<?php

namespace App\Enum;

enum FrequenciaRecorrencia: string
{
    case Mensal = 'mensal';
    case Semanal = 'semanal';
    case Anual = 'anual';

    public function rotulo(): string
    {
        return match ($this) {
            self::Mensal => 'Todo mês',
            self::Semanal => 'Toda semana',
            self::Anual => 'Todo ano',
        };
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $frequencia) => [
                'valor' => $frequencia->value,
                'rotulo' => $frequencia->rotulo(),
            ],
            self::cases()
        );
    }
}
