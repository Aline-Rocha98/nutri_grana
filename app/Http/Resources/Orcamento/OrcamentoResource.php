<?php

namespace App\Http\Resources\Orcamento;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrcamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_orcamento,
            'tipo' => $this->tipo?->value,
            'tipo_rotulo' => $this->tipo?->rotulo(),
            'id_categoria' => $this->id_categoria,
            'categoria_nome' => $this->categoria?->nome,
            'categoria_cor' => $this->categoria?->cor,
            'categoria_icone' => $this->categoria?->icone,
            'valor_mensal' => number_format((float) $this->valor_mensal, 2, ',', '.'),
            'valor_mensal_numero' => (float) $this->valor_mensal,
            'exibir_dashboard' => $this->exibir_dashboard?->value,
            'gasto_mes' => number_format((float) ($this->gasto_mes ?? 0), 2, ',', '.'),
            'gasto_mes_numero' => (float) ($this->gasto_mes ?? 0),
            'valor_restante' => number_format((float) ($this->valor_restante ?? 0), 2, ',', '.'),
            'valor_restante_numero' => (float) ($this->valor_restante ?? 0),
            'valor_excedente' => number_format((float) ($this->valor_excedente ?? 0), 2, ',', '.'),
            'valor_excedente_numero' => (float) ($this->valor_excedente ?? 0),
            'percentual' => (float) ($this->percentual ?? 0),
            'percentual_barra' => (float) ($this->percentual_barra ?? 0),
            'ultrapassado' => (bool) ($this->ultrapassado ?? false),
            'texto_progresso' => sprintf(
                '%s / %s',
                number_format((float) ($this->gasto_mes ?? 0), 2, ',', '.'),
                number_format((float) $this->valor_mensal, 2, ',', '.'),
            ),
            'mes_referencia' => $this->mes_referencia ?? null,
            'ano_referencia' => $this->ano_referencia ?? null,
            'mes_referencia_rotulo' => $this->mes_referencia_rotulo ?? null,
            'url_atualizar' => route('orcamentos.atualizar', $this->resource),
            'url_excluir' => route('orcamentos.excluir', $this->resource),
        ];
    }
}
