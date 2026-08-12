<?php

namespace App\Http\Resources\Objetivo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObjetivoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $aportes = $this->relationLoaded('aportes')
            ? AporteObjetivoResource::collection($this->aportes)->resolve()
            : [];

        return [
            'id' => $this->id_objetivo,
            'descricao' => $this->descricao,
            'valor_meta' => number_format((float) $this->valor_meta, 2, ',', '.'),
            'valor_meta_numero' => (float) $this->valor_meta,
            'data_limite' => $this->data_limite?->format('Y-m-d'),
            'data_limite_formatada' => $this->data_limite?->format('d/m/Y'),
            'exibir_dashboard' => $this->exibir_dashboard?->value,
            'valor_guardado' => number_format((float) ($this->valor_guardado ?? 0), 2, ',', '.'),
            'valor_guardado_numero' => (float) ($this->valor_guardado ?? 0),
            'valor_faltante' => number_format((float) ($this->valor_faltante ?? 0), 2, ',', '.'),
            'valor_faltante_numero' => (float) ($this->valor_faltante ?? 0),
            'percentual_atual' => (float) ($this->percentual_atual ?? 0),
            'deposito_mensal_sugerido' => number_format((float) ($this->deposito_mensal_sugerido ?? 0), 2, ',', '.'),
            'deposito_mensal_sugerido_numero' => (float) ($this->deposito_mensal_sugerido ?? 0),
            'meses_restantes' => (int) ($this->meses_restantes ?? 1),
            'valor_esperado_hoje' => number_format((float) ($this->valor_esperado_hoje ?? 0), 2, ',', '.'),
            'diferenca_ritmo' => number_format((float) ($this->diferenca_ritmo ?? 0), 2, ',', '.'),
            'diferenca_ritmo_numero' => (float) ($this->diferenca_ritmo ?? 0),
            'situacao_ritmo' => $this->situacao_ritmo ?? null,
            'situacao_ritmo_rotulo' => $this->situacao_ritmo_rotulo ?? null,
            'aportes' => $aportes,
            'url_atualizar' => route('objetivos.atualizar', $this->resource),
            'url_excluir' => route('objetivos.excluir', $this->resource),
            'url_aportar' => route('objetivos.aportes.criar', $this->resource),
        ];
    }
}
