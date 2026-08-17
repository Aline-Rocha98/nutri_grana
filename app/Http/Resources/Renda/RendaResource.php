<?php

namespace App\Http\Resources\Renda;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_renda,
            'descricao' => $this->descricao,
            'valor_esperado' => number_format((float) $this->valor_esperado, 2, ',', '.'),
            'valor_esperado_numero' => (float) $this->valor_esperado,
            'id_conta_bancaria' => $this->id_conta_bancaria,
            'conta_bancaria_nome' => $this->contaBancaria?->nome,
            'frequencia' => $this->frequencia?->value,
            'frequencia_rotulo' => $this->frequencia?->rotulo(),
            'dia_esperado' => (int) $this->dia_esperado,
            'data_inicio' => $this->data_inicio?->format('Y-m-d'),
            'observacao' => $this->observacao,
            'url_atualizar' => route('rendas.atualizar', $this->resource),
            'url_excluir' => route('rendas.excluir', $this->resource),
        ];
    }
}
