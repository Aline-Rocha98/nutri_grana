<?php

namespace App\Policies\Alocacao;

use App\Models\Alocacao\Alocacao;
use App\Models\Usuario\Usuario;

class AlocacaoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Alocacao $alocacao): bool
    {
        return $this->pertenceAoUsuario($usuario, $alocacao);
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, Alocacao $alocacao): bool
    {
        return $this->pertenceAoUsuario($usuario, $alocacao);
    }

    public function delete(Usuario $usuario, Alocacao $alocacao): bool
    {
        return $this->pertenceAoUsuario($usuario, $alocacao);
    }

    private function pertenceAoUsuario(Usuario $usuario, Alocacao $alocacao): bool
    {
        return (int) $usuario->getAuthIdentifier() === (int) $alocacao->id_usuario;
    }
}
