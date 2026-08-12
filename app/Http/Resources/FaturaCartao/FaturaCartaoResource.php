<?php

namespace App\Http\Resources\FaturaCartao;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaturaCartaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $valorTotal = (float) ($this->valor_total ?? 0);

        return [
            'id' => $this->id_fatura_cartao,
            'id_cartao_credito' => $this->id_cartao_credito,
            'cartao_nome' => $this->cartaoCredito?->nome,
            'ano' => $this->ano,
            'mes' => $this->mes,
            'competencia' => sprintf('%02d/%04d', $this->mes, $this->ano),
            'data_fechamento' => $this->data_fechamento?->format('Y-m-d'),
            'data_fechamento_formatada' => $this->data_fechamento?->format('d/m/Y'),
            'data_vencimento' => $this->data_vencimento?->format('Y-m-d'),
            'data_vencimento_formatada' => $this->data_vencimento?->format('d/m/Y'),
            'situacao' => $this->situacao?->value,
            'situacao_rotulo' => $this->situacao?->rotulo(),
            'valor_total' => number_format($valorTotal, 2, ',', '.'),
            'valor_total_numero' => $valorTotal,
            'pode_baixar' => $this->situacao?->value !== 'paga' && $valorTotal > 0,
            'url_baixar' => route('faturas-cartao.baixar', $this->resource),
            'url_detalhe' => route('faturas-cartao.show', $this->resource),
        ];
    }
}
