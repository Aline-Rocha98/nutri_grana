<?php

namespace App\Repositories\EspacoCompartilhado;

use App\Enum\StatusEspacoCompartilhado;
use App\Enum\StatusMembroEspaco;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EspacoCompartilhadoRepository
{
    public function listarEspacosAtivosDoUsuario(int $idUsuario): Collection
    {
        return $this->consultarEspacosAtivosDoUsuario($idUsuario)
            ->with(['membrosAtivos.usuario:id_usuario,nome,foto_perfil'])
            ->withCount('membrosAtivos')
            ->orderBy('nome')
            ->get();
    }

    public function listarOpcoesDeEspacosDoUsuario(int $idUsuario): Collection
    {
        return $this->consultarEspacosAtivosDoUsuario($idUsuario)
            ->orderBy('nome')
            ->get(['id_espaco_compartilhado', 'nome']);
    }

    public function criarEspacoCompartilhado(array $dados): EspacoCompartilhado
    {
        return EspacoCompartilhado::query()->create($dados);
    }

    public function atualizarEspacoCompartilhado(EspacoCompartilhado $espaco, array $dados): EspacoCompartilhado
    {
        $espaco->update($dados);

        return $espaco->refresh();
    }

    private function consultarEspacosAtivosDoUsuario(int $idUsuario): Builder
    {
        return EspacoCompartilhado::query()
            ->where('status', StatusEspacoCompartilhado::Ativo)
            ->whereHas('membros', fn (Builder $consulta) => $consulta
                ->where('id_usuario', $idUsuario)
                ->where('status', StatusMembroEspaco::Ativo));
    }
}
