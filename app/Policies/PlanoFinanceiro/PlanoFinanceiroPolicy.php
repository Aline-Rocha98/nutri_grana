<?php

namespace App\Policies\PlanoFinanceiro;

use App\Models\PlanoFinanceiro\PlanoFinanceiro;
use App\Models\Usuario\Usuario;

class PlanoFinanceiroPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, PlanoFinanceiro $planoFinanceiro): bool
    {
        return $this->pertenceAoUsuario($usuario, $planoFinanceiro);
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, PlanoFinanceiro $planoFinanceiro): bool
    {
        return $this->pertenceAoUsuario($usuario, $planoFinanceiro);
    }

    public function delete(Usuario $usuario, PlanoFinanceiro $planoFinanceiro): bool
    {
        return $this->pertenceAoUsuario($usuario, $planoFinanceiro);
    }

    private function pertenceAoUsuario(Usuario $usuario, PlanoFinanceiro $planoFinanceiro): bool
    {
        return (int) $usuario->getAuthIdentifier() === (int) $planoFinanceiro->id_usuario;
    }
}
