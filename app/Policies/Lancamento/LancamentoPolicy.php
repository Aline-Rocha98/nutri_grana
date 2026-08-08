<?php

namespace App\Policies\Lancamento;

use App\Models\Lancamento\Lancamento;
use App\Models\Usuario\Usuario;

class LancamentoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Lancamento $lancamento): bool
    {
        return $this->pertenceAoUsuario($usuario, $lancamento);
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, Lancamento $lancamento): bool
    {
        return $this->pertenceAoUsuario($usuario, $lancamento);
    }

    public function delete(Usuario $usuario, Lancamento $lancamento): bool
    {
        return $this->pertenceAoUsuario($usuario, $lancamento);
    }

    private function pertenceAoUsuario(Usuario $usuario, Lancamento $lancamento): bool
    {
        return (int) $usuario->getAuthIdentifier() === (int) $lancamento->id_usuario;
    }
}
