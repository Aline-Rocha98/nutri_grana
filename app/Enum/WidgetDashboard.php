<?php

namespace App\Enum;

enum WidgetDashboard: string
{
    case Resumo = 'resumo';
    case Contas = 'contas';
    case Cartoes = 'cartoes';
    case Categorias = 'categorias';
    case ReceitasDespesas = 'receitas_despesas';
    case Metas = 'metas';

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
