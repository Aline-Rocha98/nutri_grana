<?php

namespace App\Http\Resources\Renda;

use App\Http\Resources\PlanoFinanceiro\ItemPlanoFinanceiroResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $planoFinanceiro = $this->relationLoaded('planoFinanceiro') ? $this->planoFinanceiro : null;

        return [
            'id' => $this->id_renda,
            'chave' => $this->getRouteKey(),
            'descricao' => $this->descricao,
            'valor_esperado' => number_format((float) $this->valor_esperado, 2, ',', '.'),
            'valor_esperado_numero' => (float) $this->valor_esperado,
            'id_conta_bancaria' => $this->id_conta_bancaria,
            'conta_bancaria_nome' => $this->contaBancaria?->nome,
            'id_plano_financeiro' => $this->id_plano_financeiro,
            'plano_financeiro_nome' => $planoFinanceiro?->nome,
            'itens_plano_financeiro' => $planoFinanceiro && $planoFinanceiro->relationLoaded('itens')
                ? ItemPlanoFinanceiroResource::collection($planoFinanceiro->itens)->resolve()
                : [],
            'frequencia' => $this->frequencia?->value,
            'frequencia_rotulo' => $this->frequencia?->rotulo(),
            'dia_esperado' => (int) $this->dia_esperado,
            'data_inicio' => $this->data_inicio?->format('Y-m-d'),
            'observacao' => $this->observacao,
            'capacidade_financeira' => $this->capacidade_financeira ?? null,
            'url_atualizar' => route('rendas.atualizar', $this->resource),
            'url_excluir' => route('rendas.excluir', $this->resource),
        ];
    }
}
