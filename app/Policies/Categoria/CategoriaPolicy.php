<?php

namespace App\Policies\Categoria;

use App\Models\Categoria\Categoria;
use App\Models\Usuario\Usuario;

class CategoriaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Categoria $categoria): bool
    {
        return $this->pertenceAoUsuario($usuario, $categoria);
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, Categoria $categoria): bool
    {
        return $this->pertenceAoUsuario($usuario, $categoria);
    }

    public function delete(Usuario $usuario, Categoria $categoria): bool
    {
        return $this->pertenceAoUsuario($usuario, $categoria);
    }

    private function pertenceAoUsuario(Usuario $usuario, Categoria $categoria): bool
    {
        return (int) $usuario->getAuthIdentifier() === (int) $categoria->id_usuario;
    }
}
