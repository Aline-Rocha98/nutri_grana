<?php

namespace App\Repositories\EspacoCompartilhado;

use App\Enum\StatusRecorrenciaCompartilhada;
use App\Models\EspacoCompartilhado\RecorrenciaCompartilhada;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class RecorrenciaCompartilhadaRepository
{
    public function listarRecorrenciasAtivasIniciadasAte(int $idEspaco, CarbonInterface $limite): Collection
    {
        return RecorrenciaCompartilhada::query()
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('status', StatusRecorrenciaCompartilhada::Ativa)
            ->whereDate('data_inicio', '<=', $limite->toDateString())
            ->get();
    }

    public function criarRecorrenciaCompartilhada(array $dados): RecorrenciaCompartilhada
    {
        return RecorrenciaCompartilhada::query()->create($dados);
    }

    public function atualizarRecorrenciaCompartilhada(RecorrenciaCompartilhada $recorrencia, array $dados): RecorrenciaCompartilhada
    {
        $recorrencia->update($dados);

        return $recorrencia->refresh();
    }

    public function bloquearRecorrenciaParaAtualizacao(int $idRecorrencia): ?RecorrenciaCompartilhada
    {
        return RecorrenciaCompartilhada::query()
            ->whereKey($idRecorrencia)
            ->lockForUpdate()
            ->first();
    }
}
