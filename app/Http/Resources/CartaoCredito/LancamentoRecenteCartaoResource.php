<?php

namespace App\Http\Resources\CartaoCredito;

use App\Enum\SituacaoFatura;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LancamentoRecenteCartaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $categoria = $this->categoria;
        $situacaoFatura = $this->faturaCartao?->situacao ?? SituacaoFatura::Aberta;

        return [
            'id' => $this->id_lancamento,
            'id_cartao_credito' => $this->id_cartao_credito,
            'descricao' => $this->descricao,
            'valor' => number_format((float) $this->valor, 2, ',', '.'),
            'valor_numero' => (float) $this->valor,
            'data_vencimento' => $this->data_vencimento?->format('Y-m-d'),
            'data_vencimento_formatada' => $this->data_vencimento?->format('d/m/Y'),
            'categoria_nome' => $categoria?->nome,
            'categoria_cor' => $categoria?->cor,
            'categoria_icone' => $categoria?->icone,
            'parcela_atual' => $this->parcela_atual,
            'total_parcelas' => $this->total_parcelas,
            'eh_parcelado' => ($this->total_parcelas ?? 0) > 1,
            'situacao_fatura' => $situacaoFatura->value,
            'situacao_fatura_rotulo' => $situacaoFatura->rotulo(),
        ];
    }
}
