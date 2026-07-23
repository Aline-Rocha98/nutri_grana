<?php

namespace App\Data;

final class BancosSugeridos
{
    /**
     * @return list<array{nome: string, logo: string|null}>
     */
    public static function todos(): array
    {
        return [
            [
                'nome' => 'Nubank',
                'logo' => asset('images/logosbancos/nubank-logo.png'),
            ],
            [
                'nome' => 'Banco Inter',
                'logo' => asset('images/logosbancos/banco_inter_logo.jpg'),
            ],
            [
                'nome' => 'Itaú',
                'logo' => null,
            ],
            [
                'nome' => 'Bradesco',
                'logo' => null,
            ],
            [
                'nome' => 'Banco do Brasil',
                'logo' => null,
            ],
            [
                'nome' => 'Santander',
                'logo' => null,
            ],
            [
                'nome' => 'C6 Bank',
                'logo' => null,
            ],
            [
                'nome' => 'Caixa Econômica',
                'logo' => null,
            ],
            [
                'nome' => 'BTG Pactual',
                'logo' => null,
            ],
            [
                'nome' => 'XP Investimentos',
                'logo' => null,
            ],
            [
                'nome' => 'Carteira',
                'logo' => null,
            ],
        ];
    }

    public static function logoPorNome(?string $nome): ?string
    {
        if ($nome === null || trim($nome) === '') {
            return null;
        }

        $nomeNormalizado = mb_strtolower(trim($nome));

        foreach (self::todos() as $banco) {
            if (mb_strtolower($banco['nome']) === $nomeNormalizado) {
                return $banco['logo'];
            }
        }

        return null;
    }
}
