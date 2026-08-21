<?php

namespace App\Services\Objetivo;

use App\Enum\SimNao;
use App\Models\Objetivo\Objetivo;
use App\Repositories\Objetivo\ObjetivoRepository;
use App\Support\Dashboard\DashboardCache;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ObjetivoService
{
    public function __construct(
        private readonly ObjetivoRepository $objetivoRepository,
        private readonly CalculadoraProgressoObjetivo $calculadoraProgresso,
    ) {}

    public function listarDoUsuario(int $idUsuario): Collection
    {
        return $this->objetivoRepository
            ->listarPorUsuario($idUsuario)
            ->map(fn (Objetivo $objetivo) => $this->anexarResumo($objetivo));
    }

    public function listarParaDashboard(int $idUsuario): Collection
    {
        return $this->objetivoRepository
            ->listarParaDashboard($idUsuario)
            ->map(fn (Objetivo $objetivo) => $this->anexarResumo($objetivo));
    }

    public function criar(int $idUsuario, array $dados): Objetivo
    {
        $objetivo = $this->objetivoRepository->criar([
            'id_usuario' => $idUsuario,
            'descricao' => $dados['descricao'],
            'valor_meta' => $dados['valor_meta'],
            'data_limite' => $dados['data_limite'],
            'exibir_dashboard' => $dados['exibir_dashboard'] ?? SimNao::Nao,
        ]);

        $objetivo->setAttribute('valor_guardado', 0);

        $resumo = $this->anexarResumo($objetivo);

        DashboardCache::invalidar($idUsuario);

        return $resumo;
    }

    public function atualizar(Objetivo $objetivo, int $idUsuario, array $dados): Objetivo
    {
        $this->garantirPropriedade($objetivo, $idUsuario);

        $atualizado = $this->objetivoRepository->atualizar($objetivo, [
            'descricao' => $dados['descricao'],
            'valor_meta' => $dados['valor_meta'],
            'data_limite' => $dados['data_limite'],
            'exibir_dashboard' => $dados['exibir_dashboard'] ?? $objetivo->exibir_dashboard,
        ]);

        $atualizado->loadSum('aportes as valor_guardado', 'valor');

        $resumo = $this->anexarResumo($atualizado);

        DashboardCache::invalidar($idUsuario);

        return $resumo;
    }

    public function excluir(Objetivo $objetivo, int $idUsuario): void
    {
        $this->garantirPropriedade($objetivo, $idUsuario);

        $this->objetivoRepository->excluir($objetivo);

        DashboardCache::invalidar($idUsuario);
    }

    public function anexarResumo(Objetivo $objetivo): Objetivo
    {
        $valorGuardado = (float) ($objetivo->valor_guardado ?? 0);

        $resumo = $this->calculadoraProgresso->montarResumo(
            valorMeta: (float) $objetivo->valor_meta,
            valorGuardado: $valorGuardado,
            dataInicio: $objetivo->created_at?->copy() ?? now(),
            dataLimite: $objetivo->data_limite->copy(),
        );

        foreach ($resumo as $chave => $valor) {
            $objetivo->setAttribute($chave, $valor);
        }

        return $objetivo;
    }

    public function garantirPropriedade(Objetivo $objetivo, int $idUsuario): void
    {
        if ((int) $objetivo->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Este objetivo não pertence ao usuário autenticado.');
        }
    }
}
