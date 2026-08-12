<?php

namespace App\Repositories\Objetivo;

use App\Models\Objetivo\AporteObjetivo;
use Illuminate\Support\Collection;

class AporteObjetivoRepository
{
    public function listarPorObjetivo(int $idObjetivo): Collection
    {
        return AporteObjetivo::query()
            ->with('contaBancaria')
            ->where('id_objetivo', $idObjetivo)
            ->orderByDesc('data_aporte')
            ->orderByDesc('id_aporte_objetivo')
            ->get();
    }

    public function somarValorPorObjetivo(int $idObjetivo): float
    {
        return (float) AporteObjetivo::query()
            ->where('id_objetivo', $idObjetivo)
            ->sum('valor');
    }

    public function criar(array $dados): AporteObjetivo
    {
        return AporteObjetivo::query()->create($dados);
    }
}
