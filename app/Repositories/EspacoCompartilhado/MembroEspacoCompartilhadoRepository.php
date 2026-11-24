<?php

namespace App\Repositories\EspacoCompartilhado;

use App\Enum\PapelMembroEspaco;
use App\Enum\StatusMembroEspaco;
use App\Models\EspacoCompartilhado\MembroEspacoCompartilhado;
use Illuminate\Support\Collection;

class MembroEspacoCompartilhadoRepository
{
    public function buscarVinculoDoUsuarioNoEspaco(int $idEspaco, int $idUsuario): ?MembroEspacoCompartilhado
    {
        return MembroEspacoCompartilhado::query()
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('id_usuario', $idUsuario)
            ->first();
    }

    public function buscarVinculoAtivoDoUsuarioNoEspaco(int $idEspaco, int $idUsuario): ?MembroEspacoCompartilhado
    {
        return MembroEspacoCompartilhado::query()
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('id_usuario', $idUsuario)
            ->where('status', StatusMembroEspaco::Ativo)
            ->first();
    }

    public function usuarioEhMembroAtivoDoEspaco(int $idEspaco, int $idUsuario): bool
    {
        return MembroEspacoCompartilhado::query()
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('id_usuario', $idUsuario)
            ->where('status', StatusMembroEspaco::Ativo)
            ->exists();
    }

    public function usuarioEhAdministradorAtivoDoEspaco(int $idEspaco, int $idUsuario): bool
    {
        return MembroEspacoCompartilhado::query()
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('id_usuario', $idUsuario)
            ->where('status', StatusMembroEspaco::Ativo)
            ->where('papel', PapelMembroEspaco::Administrador)
            ->exists();
    }

    public function contarAdministradoresAtivosDoEspaco(int $idEspaco): int
    {
        return MembroEspacoCompartilhado::query()
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('status', StatusMembroEspaco::Ativo)
            ->where('papel', PapelMembroEspaco::Administrador)
            ->count();
    }

    public function contarMembrosAtivosDoEspaco(int $idEspaco): int
    {
        return MembroEspacoCompartilhado::query()
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('status', StatusMembroEspaco::Ativo)
            ->count();
    }

    public function listarMembrosAtivosDoEspaco(int $idEspaco): Collection
    {
        return MembroEspacoCompartilhado::query()
            ->with('usuario:id_usuario,nome,foto_perfil')
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('status', StatusMembroEspaco::Ativo)
            ->orderByRaw("CASE WHEN papel = 'administrador' THEN 0 ELSE 1 END")
            ->orderBy('entrou_em')
            ->get();
    }

    public function todosSaoMembrosAtivosDoEspaco(int $idEspaco, array $idsUsuarios): bool
    {
        $idsUnicos = array_values(array_unique(array_map('intval', $idsUsuarios)));

        if ($idsUnicos === []) {
            return true;
        }

        $quantidadeEncontrada = MembroEspacoCompartilhado::query()
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('status', StatusMembroEspaco::Ativo)
            ->whereIn('id_usuario', $idsUnicos)
            ->count();

        return $quantidadeEncontrada === count($idsUnicos);
    }

    public function listarVinculosAtivosDoUsuario(int $idUsuario): Collection
    {
        return MembroEspacoCompartilhado::query()
            ->with('espaco')
            ->where('id_usuario', $idUsuario)
            ->where('status', StatusMembroEspaco::Ativo)
            ->get();
    }

    public function buscarMembroAtivoMaisAntigoDoEspaco(int $idEspaco, int $idUsuarioIgnorado): ?MembroEspacoCompartilhado
    {
        return MembroEspacoCompartilhado::query()
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('status', StatusMembroEspaco::Ativo)
            ->where('id_usuario', '!=', $idUsuarioIgnorado)
            ->orderBy('entrou_em')
            ->orderBy('id_membro_espaco')
            ->first();
    }

    public function criarVinculoDeMembro(array $dados): MembroEspacoCompartilhado
    {
        return MembroEspacoCompartilhado::query()->create($dados);
    }

    public function atualizarVinculoDeMembro(MembroEspacoCompartilhado $membro, array $dados): MembroEspacoCompartilhado
    {
        $membro->update($dados);

        return $membro->refresh();
    }
}
