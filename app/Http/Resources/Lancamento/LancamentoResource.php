<?php

namespace App\Http\Resources\Lancamento;

use App\Http\Resources\PlanoFinanceiro\ItemPlanoFinanceiroResource;
use App\Models\PlanoFinanceiro\PlanoFinanceiro;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LancamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $categoria = $this->categoria;
        $idCategoriaPrincipal = null;
        $idSubcategoria = null;

        if ($categoria) {
            if ($categoria->id_categoria_pai) {
                $idCategoriaPrincipal = $categoria->id_categoria_pai;
                $idSubcategoria = $categoria->id_categoria;
            } else {
                $idCategoriaPrincipal = $categoria->id_categoria;
            }
        }

        $pai = $this->pai;
        $ehRecorrente = $this->eh_recorrencia?->value === 'S' || $this->id_lancamento_pai !== null;
        $ehParcelado = ($this->total_parcelas ?? 0) > 1;

        return [
            'id' => $this->id_lancamento,
            'chave' => $this->getRouteKey(),
            'descricao' => $this->descricao,
            'valor' => number_format((float) $this->valor, 2, ',', '.'),
            'valor_numero' => (float) $this->valor,
            'data_vencimento' => $this->data_vencimento?->format('Y-m-d'),
            'data_vencimento_formatada' => $this->data_vencimento?->format('d/m/Y'),
            'data_pagamento' => $this->data_pagamento?->format('Y-m-d'),
            'tipo' => $this->tipo?->value,
            'tipo_rotulo' => $this->tipo?->rotulo(),
            'forma_pagamento' => $this->forma_pagamento?->value,
            'forma_pagamento_rotulo' => $this->forma_pagamento?->rotulo(),
            'id_conta_bancaria' => $this->id_conta_bancaria,
            'conta_bancaria_nome' => $this->contaBancaria?->nome,
            'id_cartao_credito' => $this->id_cartao_credito,
            'cartao_credito_nome' => $this->cartaoCredito?->nome,
            'id_fatura_cartao' => $this->id_fatura_cartao,
            'situacao' => $this->situacao?->value,
            'situacao_rotulo' => $this->situacao?->rotulo(),
            'esta_efetivado' => (bool) $this->situacao?->estaEfetivado(),
            'id_categoria' => $this->id_categoria,
            'id_categoria_principal' => $idCategoriaPrincipal,
            'id_subcategoria' => $idSubcategoria,
            'categoria_nome' => $categoria?->nome,
            'categoria_cor' => $categoria?->cor,
            'categoria_icone' => $categoria?->icone,
            'id_renda' => $this->id_renda,
            'eh_renda' => $this->id_renda !== null,
            'valor_previsto' => $this->valor_previsto !== null
                ? number_format((float) $this->valor_previsto, 2, ',', '.')
                : null,
            'valor_previsto_numero' => $this->valor_previsto !== null
                ? (float) $this->valor_previsto
                : null,
            'observacao' => $this->observacao,
            'parcela_atual' => $this->parcela_atual,
            'total_parcelas' => $this->total_parcelas,
            'id_grupo_parcela' => $this->id_grupo_parcela,
            'id_lancamento_pai' => $this->id_lancamento_pai,
            'eh_recorrencia' => $this->eh_recorrencia?->value,
            'eh_recorrente' => $ehRecorrente,
            'eh_parcelado' => $ehParcelado,
            'frequencia_recorrencia' => $this->frequencia_recorrencia?->value
                ?? $pai?->frequencia_recorrencia?->value,
            'recorrencia_ate' => ($this->recorrencia_ate ?? $pai?->recorrencia_ate)?->format('Y-m-d'),
            'url_atualizar' => route('lancamentos.atualizar', $this->resource),
            'url_excluir' => route('lancamentos.excluir', $this->resource),
            'url_situacao' => route('lancamentos.situacao', $this->resource),
            'url_confirmar_receita' => $this->id_renda
                ? route('lancamentos.confirmar-receita', $this->resource)
                : null,
            'plano_financeiro_nome' => $this->montarPlanoFinanceiroDaRenda()?->nome,
            'itens_plano_financeiro' => $this->montarItensQueSeparamDoPlanoDaRenda(),
        ];
    }

    private function montarPlanoFinanceiroDaRenda(): ?PlanoFinanceiro
    {
        if ($this->id_renda === null || ! $this->relationLoaded('renda') || $this->renda === null) {
            return null;
        }

        return $this->renda->relationLoaded('planoFinanceiro') ? $this->renda->planoFinanceiro : null;
    }

    private function montarItensQueSeparamDoPlanoDaRenda(): array
    {
        $planoFinanceiro = $this->montarPlanoFinanceiroDaRenda();

        if (! $planoFinanceiro || ! $planoFinanceiro->relationLoaded('itensQueSeparamDoSaldo')) {
            return [];
        }

        return ItemPlanoFinanceiroResource::collection($planoFinanceiro->itensQueSeparamDoSaldo)->resolve();
    }
}
