<?php

namespace App\Enum;

enum StatusOrcamentoServico: string
{
    case EmAnalise = 'em_analise';
    case Aprovada = 'aprovada';
    case Recusada = 'recusada';
    case Expirada = 'expirada';
    case Concluida = 'concluida';

    public function rotulo(): string
    {
        return match ($this) {
            self::EmAnalise => 'Em análise',
            self::Aprovada => 'Aprovada',
            self::Recusada => 'Recusada',
            self::Expirada => 'Expirada',
            self::Concluida => 'Concluída',
        };
    }

    public function podeEditar(): bool
    {
        return $this === self::EmAnalise;
    }

    public function podeAprovar(): bool
    {
        return $this === self::EmAnalise;
    }

    public function podeRecusar(): bool
    {
        return $this === self::EmAnalise;
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $status) => [
                'valor' => $status->value,
                'rotulo' => $status->rotulo(),
            ],
            self::cases()
        );
    }
}
