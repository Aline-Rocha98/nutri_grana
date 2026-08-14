<?php

namespace App\Policies\Orcamento;

use App\Models\Orcamento\OrcamentoServico;
use App\Models\Usuario\Usuario;

class OrcamentoServicoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, OrcamentoServico $orcamentoServico): bool
    {
        return $this->pertenceAoUsuario($usuario, $orcamentoServico);
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, OrcamentoServico $orcamentoServico): bool
    {
        return $this->pertenceAoUsuario($usuario, $orcamentoServico);
    }

    public function delete(Usuario $usuario, OrcamentoServico $orcamentoServico): bool
    {
        return $this->pertenceAoUsuario($usuario, $orcamentoServico);
    }

    private function pertenceAoUsuario(Usuario $usuario, OrcamentoServico $orcamentoServico): bool
    {
        return (int) $usuario->getAuthIdentifier() === (int) $orcamentoServico->id_usuario;
    }
}
