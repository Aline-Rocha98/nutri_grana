<?php

namespace App\Enum;

enum SituacaoRitmoObjetivo: string
{
    case Adiantado = 'adiantado';
    case Atrasado = 'atrasado';
    case EmDia = 'em_dia';
    case Concluido = 'concluido';
    case Vencido = 'vencido';

    public function rotulo(): string
    {
        return match ($this) {
            self::Adiantado => 'Adiantado',
            self::Atrasado => 'Atrasado',
            self::EmDia => 'Em dia',
            self::Concluido => 'Concluído',
            self::Vencido => 'Vencido',
        };
    }
}
