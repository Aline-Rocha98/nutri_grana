<?php

namespace App\Repositories\Objetivo;

use App\Enum\SimNao;
use App\Models\Objetivo\Objetivo;
use Illuminate\Support\Collection;

class ObjetivoRepository
{
    public function listarPorUsuario(int $idUsuario): Collection
    {
        return Objetivo::query()
            ->where('id_usuario', $idUsuario)
            ->withSum('aportes as valor_guardado', 'valor')
            ->with(['aportes' => fn ($query) => $query
                ->with('contaBancaria')
                ->orderByDesc('data_aporte')
                ->orderByDesc('id_aporte_objetivo')])
            ->orderBy('data_limite')
            ->orderBy('descricao')
            ->get();
    }

    public function listarParaDashboard(int $idUsuario): Collection
    {
        return Objetivo::query()
            ->where('id_usuario', $idUsuario)
            ->where('exibir_dashboard', SimNao::Sim)
            ->withSum('aportes as valor_guardado', 'valor')
            ->orderBy('data_limite')
            ->get();
    }

    public function buscarParaUsuario(int $idObjetivo, int $idUsuario): ?Objetivo
    {
        return Objetivo::query()
            ->where('id_objetivo', $idObjetivo)
            ->where('id_usuario', $idUsuario)
            ->withSum('aportes as valor_guardado', 'valor')
            ->first();
    }

    public function criar(array $dados): Objetivo
    {
        return Objetivo::query()->create($dados);
    }

    public function atualizar(Objetivo $objetivo, array $dados): Objetivo
    {
        $objetivo->update($dados);

        return $objetivo->refresh();
    }

    public function excluir(Objetivo $objetivo): void
    {
        $objetivo->delete();
    }

    public function temAportes(Objetivo $objetivo): bool
    {
        return $objetivo->aportes()->exists();
    }
}
