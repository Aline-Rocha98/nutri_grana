<?php

namespace App\Enum;

enum FrequenciaRecorrencia: string
{
    case Mensal = 'mensal';
    case Semanal = 'semanal';
    case Anual = 'anual';
    case ACadaXDias = 'a_cada_x_dias';

    public function rotulo(): string
    {
        return match ($this) {
            self::Mensal => 'Todo mês',
            self::Semanal => 'Toda semana',
            self::Anual => 'Todo ano',
            self::ACadaXDias => 'A cada X dias',
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
