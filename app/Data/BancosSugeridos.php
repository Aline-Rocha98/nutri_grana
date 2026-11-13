<?php

namespace App\Data;

use App\Enum\AcabamentoVisual;
use App\Enum\BandeiraCartaoCredito;

final class BancosSugeridos
{
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

    public static function modelosRapidos(): array
    {
        $predefinicoesPorBanco = [
            'Nubank' => [AcabamentoVisual::Roxo, BandeiraCartaoCredito::Mastercard],
            'Banco Inter' => [AcabamentoVisual::Laranja, BandeiraCartaoCredito::Mastercard],
            'Itaú' => [AcabamentoVisual::Oceano, BandeiraCartaoCredito::Visa],
            'Bradesco' => [AcabamentoVisual::Prata, BandeiraCartaoCredito::Visa],
        ];

        $bancosPorNome = array_column(self::todos(), null, 'nome');
        $modelosRapidos = [];

        foreach ($predefinicoesPorBanco as $nome => [$acabamento, $bandeira]) {
            $modelosRapidos[] = [
                'nome' => $nome,
                'logo' => $bancosPorNome[$nome]['logo'] ?? null,
                'acabamento' => $acabamento->value,
                'bandeira' => $bandeira->value,
            ];
        }

        return $modelosRapidos;
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
