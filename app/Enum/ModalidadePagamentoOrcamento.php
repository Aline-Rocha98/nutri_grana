<?php

namespace App\Enum;

enum ModalidadePagamentoOrcamento: string
{
    case AVista = 'a_vista';
    case Parcelado = 'parcelado';

    public function rotulo(): string
    {
        return match ($this) {
            self::AVista => 'À vista',
            self::Parcelado => 'Parcelado',
        };
    }

    public static function opcoesParaSelect(): array
    {
        return array_map(
            fn (self $modalidade) => [
                'valor' => $modalidade->value,
                'rotulo' => $modalidade->rotulo(),
            ],
            self::cases()
        );
    }
}
