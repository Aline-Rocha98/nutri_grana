<?php

namespace App\Http\Resources\ContaBancaria;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimentacaoRecenteContaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $categoria = $this->categoria;

        return [
            'id' => $this->id_lancamento,
            'id_conta_bancaria' => $this->id_conta_bancaria,
            'descricao' => $this->descricao,
            'valor' => number_format((float) $this->valor, 2, ',', '.'),
            'valor_numero' => (float) $this->valor,
            'tipo' => $this->tipo?->value,
            'data_vencimento' => $this->data_vencimento?->format('Y-m-d'),
            'data_vencimento_formatada' => $this->data_vencimento?->format('d/m/Y'),
            'categoria_nome' => $categoria?->nome,
            'categoria_cor' => $categoria?->cor,
            'categoria_icone' => $categoria?->icone,
            'parcela_atual' => $this->parcela_atual,
            'total_parcelas' => $this->total_parcelas,
            'eh_parcelado' => ($this->total_parcelas ?? 0) > 1,
            'situacao' => $this->situacao?->value,
            'situacao_rotulo' => $this->situacao?->rotulo(),
        ];
    }
}
