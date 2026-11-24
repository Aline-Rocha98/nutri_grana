<?php

namespace App\Enum;

enum OrigemLancamentoFinanceiro: string
{
    case Pessoal = 'pessoal';
    case Compartilhado = 'compartilhado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pessoal => 'Pessoal',
            self::Compartilhado => 'Compartilhado',
        };
    }
}
