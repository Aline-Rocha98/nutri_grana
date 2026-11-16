<?php

namespace App\Exceptions\EspacoCompartilhado;

use RuntimeException;

class RespostaConviteInvalidaException extends RuntimeException
{
    public function __construct(
        public readonly string $campo,
        string $mensagem,
    ) {
        parent::__construct($mensagem);
    }

    public function obterErrosPorCampo(): array
    {
        return [$this->campo => $this->getMessage()];
    }
}
