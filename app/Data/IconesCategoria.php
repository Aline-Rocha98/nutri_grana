<?php

namespace App\Data;

final class IconesCategoria
{
    /**
     * @return list<string>
     */
    public static function todos(): array
    {
        return [
            'restaurant',
            'directions_car',
            'sports_esports',
            'subscriptions',
            'school',
            'local_hospital',
            'more_horiz',
            'payments',
            'work',
            'home',
            'shopping_cart',
            'flight',
            'pets',
            'fitness_center',
            'movie',
            'phone_iphone',
            'wifi',
            'savings',
            'account_balance',
            'card_giftcard',
            'category',
        ];
    }

    /**
     * @return list<array{valor: string, rotulo: string}>
     */
    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (string $icone) => [
                'valor' => $icone,
                'rotulo' => $icone,
            ],
            self::todos()
        );
    }
}
