<?php

namespace App\Policies\Usuario;

use App\Models\Usuario\Usuario;

class UsuarioPolicy
{
    public function view(Usuario $autenticado, Usuario $usuario): bool
    {
        return $this->ehOProprioUsuario($autenticado, $usuario);
    }

    public function update(Usuario $autenticado, Usuario $usuario): bool
    {
        return $this->ehOProprioUsuario($autenticado, $usuario);
    }

    public function delete(Usuario $autenticado, Usuario $usuario): bool
    {
        return $this->ehOProprioUsuario($autenticado, $usuario);
    }

    public function changePassword(Usuario $autenticado, Usuario $usuario): bool
    {
        return $this->ehOProprioUsuario($autenticado, $usuario);
    }

    private function ehOProprioUsuario(Usuario $autenticado, Usuario $usuario): bool
    {
        return (int) $autenticado->getAuthIdentifier() === (int) $usuario->id_usuario;
    }
}
