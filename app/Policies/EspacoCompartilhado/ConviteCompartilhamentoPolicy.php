<?php

namespace App\Policies\EspacoCompartilhado;

use App\Models\EspacoCompartilhado\ConviteCompartilhamento;
use App\Models\Usuario\Usuario;
use App\Repositories\EspacoCompartilhado\MembroEspacoCompartilhadoRepository;

class ConviteCompartilhamentoPolicy
{
    public function __construct(
        private readonly MembroEspacoCompartilhadoRepository $membroEspacoRepository,
    ) {}

    public function responder(Usuario $usuario, ConviteCompartilhamento $convite): bool
    {
        return $convite->id_usuario_convidado !== null
            && (int) $convite->id_usuario_convidado === (int) $usuario->getAuthIdentifier();
    }

    public function revogar(Usuario $usuario, ConviteCompartilhamento $convite): bool
    {
        return $this->membroEspacoRepository->usuarioEhAdministradorAtivoDoEspaco(
            (int) $convite->id_espaco_compartilhado,
            (int) $usuario->getAuthIdentifier()
        );
    }
}
