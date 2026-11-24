<?php

namespace App\Policies\EspacoCompartilhado;

use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\Usuario\Usuario;
use App\Repositories\EspacoCompartilhado\MembroEspacoCompartilhadoRepository;
use Illuminate\Auth\Access\Response;

class EspacoCompartilhadoPolicy
{
    public function __construct(
        private readonly MembroEspacoCompartilhadoRepository $membroEspacoRepository,
    ) {}

    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, EspacoCompartilhado $espaco): bool
    {
        return $espaco->estaAtivo() && $this->ehMembroAtivo($usuario, $espaco);
    }

    public function update(Usuario $usuario, EspacoCompartilhado $espaco): bool
    {
        return $espaco->estaAtivo() && $this->ehAdministradorAtivo($usuario, $espaco);
    }

    public function arquivar(Usuario $usuario, EspacoCompartilhado $espaco): bool
    {
        return $this->update($usuario, $espaco);
    }

    public function gerenciarMembros(Usuario $usuario, EspacoCompartilhado $espaco): bool
    {
        return $this->update($usuario, $espaco);
    }

    public function convidar(Usuario $usuario, EspacoCompartilhado $espaco): bool
    {
        return $this->update($usuario, $espaco);
    }

    public function sair(Usuario $usuario, EspacoCompartilhado $espaco): Response
    {
        if (! $this->view($usuario, $espaco)) {
            return Response::deny('Você não participa deste espaço.');
        }

        $idEspaco = (int) $espaco->id_espaco_compartilhado;
        $ehUnicoAdministrador = $this->ehAdministradorAtivo($usuario, $espaco)
            && $this->membroEspacoRepository->contarAdministradoresAtivosDoEspaco($idEspaco) <= 1;
        $possuiOutrosMembros = $this->membroEspacoRepository->contarMembrosAtivosDoEspaco($idEspaco) > 1;

        if ($ehUnicoAdministrador && $possuiOutrosMembros) {
            return Response::deny('Você é o único administrador. Arquive o espaço ou peça para outro administrador assumir antes de sair.');
        }

        return Response::allow();
    }

    private function ehMembroAtivo(Usuario $usuario, EspacoCompartilhado $espaco): bool
    {
        return $this->membroEspacoRepository->usuarioEhMembroAtivoDoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            (int) $usuario->getAuthIdentifier()
        );
    }

    private function ehAdministradorAtivo(Usuario $usuario, EspacoCompartilhado $espaco): bool
    {
        return $this->membroEspacoRepository->usuarioEhAdministradorAtivoDoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            (int) $usuario->getAuthIdentifier()
        );
    }
}
