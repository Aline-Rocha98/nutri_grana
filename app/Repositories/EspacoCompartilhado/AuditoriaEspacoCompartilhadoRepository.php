<?php

namespace App\Repositories\EspacoCompartilhado;

use App\Models\EspacoCompartilhado\AuditoriaEspacoCompartilhado;
use Illuminate\Support\Collection;

class AuditoriaEspacoCompartilhadoRepository
{
    public function registrarAuditoria(array $dados): AuditoriaEspacoCompartilhado
    {
        return AuditoriaEspacoCompartilhado::query()->create($dados);
    }

    public function listarHistoricoRecenteDoEspaco(int $idEspaco, int $limite): Collection
    {
        return AuditoriaEspacoCompartilhado::query()
            ->with('usuario:id_usuario,nome,foto_perfil')
            ->where('id_espaco_compartilhado', $idEspaco)
            ->orderByDesc('created_at')
            ->orderByDesc('id_auditoria')
            ->limit($limite)
            ->get();
    }
}
