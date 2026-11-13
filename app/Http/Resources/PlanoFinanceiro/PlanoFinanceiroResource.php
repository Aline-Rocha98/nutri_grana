<?php

namespace App\Http\Resources\PlanoFinanceiro;

use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanoFinanceiroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $itens = $this->relationLoaded('itens') ? $this->itens : collect();
        $itensPercentuais = $itens->filter(fn (ItemPlanoFinanceiro $item) => $item->ehPercentual());
        $itensValorFixo = $itens->reject(fn (ItemPlanoFinanceiro $item) => $item->ehPercentual());

        return [
            'id' => $this->id_plano_financeiro,
            'chave' => $this->getRouteKey(),
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'situacao' => $this->situacao?->value,
            'situacao_rotulo' => $this->situacao?->rotulo(),
            'esta_ativo' => $this->estaAtivo(),
            'padrao' => $this->ehPadrao(),
            'itens' => ItemPlanoFinanceiroResource::collection($itens)->resolve(),
            'percentual_total' => round($itensPercentuais->sum(fn (ItemPlanoFinanceiro $item) => (float) $item->valor), 2),
            'valor_fixo_total' => round($itensValorFixo->sum(fn (ItemPlanoFinanceiro $item) => (float) $item->valor), 2),
            'quantidade_rendas' => $this->whenCounted('rendas', fn ($total) => (int) $total, 0),
            'url_atualizar' => route('planos-financeiros.atualizar', $this->resource),
            'url_alternar_situacao' => route('planos-financeiros.alternar-situacao', $this->resource),
            'url_definir_padrao' => route('planos-financeiros.definir-padrao', $this->resource),
            'url_excluir' => route('planos-financeiros.excluir', $this->resource),
        ];
    }
}
