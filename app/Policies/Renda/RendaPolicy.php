<?php

namespace App\Policies\Renda;

use App\Models\Renda\Renda;
use App\Models\Usuario\Usuario;

class RendaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Renda $renda): bool
    {
        return $this->pertenceAoUsuario($usuario, $renda);
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, Renda $renda): bool
    {
        return $this->pertenceAoUsuario($usuario, $renda);
    }

    public function delete(Usuario $usuario, Renda $renda): bool
    {
        return $this->pertenceAoUsuario($usuario, $renda);
    }

    private function pertenceAoUsuario(Usuario $usuario, Renda $renda): bool
    {
        return (int) $usuario->getAuthIdentifier() === (int) $renda->id_usuario;
    }
}
