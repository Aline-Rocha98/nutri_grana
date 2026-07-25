<?php

namespace App\Policies\ContasBancarias;

use App\Models\ContasBancarias\ContaBancaria;
use App\Models\Usuario\Usuario;

class ContaBancariaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, ContaBancaria $contaBancaria): bool
    {
        return $this->pertenceAoUsuario($usuario, $contaBancaria);
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, ContaBancaria $contaBancaria): bool
    {
        return $this->pertenceAoUsuario($usuario, $contaBancaria);
    }

    public function delete(Usuario $usuario, ContaBancaria $contaBancaria): bool
    {
        return $this->pertenceAoUsuario($usuario, $contaBancaria);
    }

    private function pertenceAoUsuario(Usuario $usuario, ContaBancaria $contaBancaria): bool
    {
        return (int) $usuario->getAuthIdentifier() === (int) $contaBancaria->id_usuario;
    }
}
