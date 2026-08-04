<?php

namespace App\Data;

use App\Enum\TipoCategoria;

final class CategoriaPadrao
{
    public const COR_PADRAO = '#6b7280';

    /**
     * @return list<array{nome: string, tipo: string, icone: string, cor: string}>
     */
    public static function todas(): array
    {
        return [
            [
                'nome' => 'Alimentação',
                'tipo' => TipoCategoria::Saida->value,
                'icone' => 'restaurant',
                'cor' => '#ef4444',
            ],
            [
                'nome' => 'Transporte',
                'tipo' => TipoCategoria::Saida->value,
                'icone' => 'directions_car',
                'cor' => '#3b82f6',
            ],
            [
                'nome' => 'Lazer',
                'tipo' => TipoCategoria::Saida->value,
                'icone' => 'sports_esports',
                'cor' => '#a855f7',
            ],
            [
                'nome' => 'Assinaturas',
                'tipo' => TipoCategoria::Saida->value,
                'icone' => 'subscriptions',
                'cor' => '#f59e0b',
            ],
            [
                'nome' => 'Educação',
                'tipo' => TipoCategoria::Saida->value,
                'icone' => 'school',
                'cor' => '#06b6d4',
            ],
            [
                'nome' => 'Saúde',
                'tipo' => TipoCategoria::Saida->value,
                'icone' => 'local_hospital',
                'cor' => '#10b981',
            ],
            [
                'nome' => 'Outros',
                'tipo' => TipoCategoria::Saida->value,
                'icone' => 'more_horiz',
                'cor' => self::COR_PADRAO,
            ],
            [
                'nome' => 'Salário',
                'tipo' => TipoCategoria::Entrada->value,
                'icone' => 'payments',
                'cor' => '#1fa67e',
            ],
            [
                'nome' => 'Freelance',
                'tipo' => TipoCategoria::Entrada->value,
                'icone' => 'work',
                'cor' => '#22c55e',
            ],
        ];
    }
}
