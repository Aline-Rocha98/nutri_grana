<?php

namespace App\Policies\CartoesCredito;

use App\Models\CartoesCredito\CartaoCredito;
use App\Models\Usuario\Usuario;

class CartaoCreditoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, CartaoCredito $cartaoCredito): bool
    {
        return $this->pertenceAoUsuario($usuario, $cartaoCredito);
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, CartaoCredito $cartaoCredito): bool
    {
        return $this->pertenceAoUsuario($usuario, $cartaoCredito);
    }

    public function delete(Usuario $usuario, CartaoCredito $cartaoCredito): bool
    {
        return $this->pertenceAoUsuario($usuario, $cartaoCredito);
    }

    private function pertenceAoUsuario(Usuario $usuario, CartaoCredito $cartaoCredito): bool
    {
        return (int) $usuario->getAuthIdentifier() === (int) $cartaoCredito->id_usuario;
    }
}
