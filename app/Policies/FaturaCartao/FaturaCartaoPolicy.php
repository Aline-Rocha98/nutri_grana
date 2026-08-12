<?php

namespace App\Policies\FaturaCartao;

use App\Models\FaturaCartao\FaturaCartao;
use App\Models\Usuario\Usuario;

class FaturaCartaoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, FaturaCartao $faturaCartao): bool
    {
        return $this->pertenceAoUsuario($usuario, $faturaCartao);
    }

    public function update(Usuario $usuario, FaturaCartao $faturaCartao): bool
    {
        return $this->pertenceAoUsuario($usuario, $faturaCartao);
    }

    private function pertenceAoUsuario(Usuario $usuario, FaturaCartao $faturaCartao): bool
    {
        return (int) $usuario->getAuthIdentifier() === (int) $faturaCartao->id_usuario;
    }
}
