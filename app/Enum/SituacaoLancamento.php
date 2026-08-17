<?php

namespace App\Enum;

enum SituacaoLancamento: string
{
    case Pendente = 'pendente';
    case Pago = 'pago';
    case Cancelado = 'cancelado';
    case Previsto = 'previsto';
    case Recebido = 'recebido';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Pago => 'Pago',
            self::Cancelado => 'Cancelado',
            self::Previsto => 'Previsto',
            self::Recebido => 'Recebido',
        };
    }

    public function estaEfetivado(): bool
    {
        return $this === self::Pago || $this === self::Recebido;
    }

    public function estaAberto(): bool
    {
        return $this === self::Pendente || $this === self::Previsto;
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
