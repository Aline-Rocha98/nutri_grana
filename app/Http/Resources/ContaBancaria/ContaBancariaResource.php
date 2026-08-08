<?php

namespace App\Http\Resources\ContaBancaria;

use App\Data\BancosSugeridos;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContaBancariaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_conta_bancaria,
            'nome' => $this->nome,
            'saldo_inicial' => number_format((float) $this->saldo_inicial, 2, ',', '.'),
            'saldo_inicial_numero' => (float) $this->saldo_inicial,
            'saldo_atual' => number_format((float) ($this->saldo_atual ?? $this->saldo_inicial), 2, ',', '.'),
            'saldo_atual_numero' => (float) ($this->saldo_atual ?? $this->saldo_inicial),
            'tipo' => $this->tipo?->value,
            'tipo_rotulo' => $this->tipo?->rotulo(),
            'arquivada' => $this->arquivada?->value,
            'padrao_desconto' => $this->padrao_desconto?->value,
            'exibir_resumo' => $this->exibir_resumo?->value,
            'total_lancamentos' => (int) ($this->total_lancamentos ?? 0),
            'logo' => BancosSugeridos::logoPorNome($this->nome),
            'url_atualizar' => route('contas-bancarias.atualizar', $this->resource),
            'url_excluir' => route('contas-bancarias.excluir', $this->resource),
        ];
    }
}
