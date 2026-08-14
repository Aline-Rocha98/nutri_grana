<?php

namespace App\Policies\Orcamento;

use App\Models\Orcamento\Orcamento;
use App\Models\Usuario\Usuario;

class OrcamentoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Orcamento $orcamento): bool
    {
        return $this->pertenceAoUsuario($usuario, $orcamento);
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, Orcamento $orcamento): bool
    {
        return $this->pertenceAoUsuario($usuario, $orcamento);
    }

    public function delete(Usuario $usuario, Orcamento $orcamento): bool
    {
        return $this->pertenceAoUsuario($usuario, $orcamento);
    }

    private function pertenceAoUsuario(Usuario $usuario, Orcamento $orcamento): bool
    {
        return (int) $usuario->getAuthIdentifier() === (int) $orcamento->id_usuario;
    }
}
