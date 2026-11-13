<?php

namespace App\Enum;

enum SituacaoPlanoFinanceiro: string
{
    case Ativo = 'ativo';
    case Inativo = 'inativo';

    public function rotulo(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Inativo => 'Inativo',
        };
    }

    public function alternar(): self
    {
        return $this === self::Ativo ? self::Inativo : self::Ativo;
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
