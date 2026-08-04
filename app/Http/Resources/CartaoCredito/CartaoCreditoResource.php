<?php

namespace App\Http\Resources\CartaoCredito;

use App\Data\BancosSugeridos;
use App\Support\Data\FormatarDia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartaoCreditoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $temFaturaAberta = (bool) ($this->tem_fatura_aberta ?? false);

        return [
            'id' => $this->id_cartao_credito,
            'nome' => $this->nome,
            'limite_total' => number_format((float) $this->limite_total, 2, ',', '.'),
            'limite_total_numero' => (float) $this->limite_total,
            'dia_fechamento' => (int) $this->dia_fechamento,
            'dia_fechamento_formatado' => FormatarDia::pad($this->dia_fechamento),
            'dia_vencimento' => (int) $this->dia_vencimento,
            'dia_vencimento_formatado' => FormatarDia::pad($this->dia_vencimento),
            'bandeira' => $this->bandeira?->value,
            'bandeira_rotulo' => $this->bandeira?->rotulo(),
            'padrao' => $this->padrao?->value,
            'arquivada' => $this->arquivada?->value,
            'tem_fatura_aberta' => $temFaturaAberta,
            'pode_excluir' => ! $temFaturaAberta,
            'logo' => BancosSugeridos::logoPorNome($this->nome),
            'url_atualizar' => route('cartoes-credito.atualizar', $this->resource),
            'url_arquivar' => route('cartoes-credito.arquivar', $this->resource),
            'url_excluir' => route('cartoes-credito.excluir', $this->resource),
        ];
    }
}
