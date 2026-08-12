<?php

namespace App\Policies\Objetivo;

use App\Models\Objetivo\Objetivo;
use App\Models\Usuario\Usuario;

class ObjetivoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Objetivo $objetivo): bool
    {
        return $this->pertenceAoUsuario($usuario, $objetivo);
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, Objetivo $objetivo): bool
    {
        return $this->pertenceAoUsuario($usuario, $objetivo);
    }

    public function delete(Usuario $usuario, Objetivo $objetivo): bool
    {
        return $this->pertenceAoUsuario($usuario, $objetivo);
    }

    private function pertenceAoUsuario(Usuario $usuario, Objetivo $objetivo): bool
    {
        return (int) $usuario->getAuthIdentifier() === (int) $objetivo->id_usuario;
    }
}
