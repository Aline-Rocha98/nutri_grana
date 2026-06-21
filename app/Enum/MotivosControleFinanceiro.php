<?php

namespace App\Enum;

enum MotivosControleFinanceiro: string
{
    case ORGANIZAR_GASTOS = 'Organizar gastos';
    case ECONOMIZAR_DINHEIRO = 'Economizar dinheiro';
    case ALCANCAR_OBJETIVOS = 'Alcançar objetivos';
    case CONTROLAR_DIBIDADES = 'Controlar dívidas';
    case PLANEJAMENTO_FUTURO = 'Planejamento futuro';
    case OUTRO = 'Outro';

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $motivo) => [
                'value' => $motivo->value,
                'label' => $motivo->value,
            ],
            self::cases()
        );
    }
}
