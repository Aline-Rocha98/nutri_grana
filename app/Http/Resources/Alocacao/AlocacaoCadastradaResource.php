<?php

namespace App\Http\Resources\Alocacao;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlocacaoCadastradaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalItensPlano = (int) ($this->itens_plano_count ?? 0);
        $totalMovimentacoes = (int) ($this->movimentacoes_count ?? 0) + (int) ($this->movimentacoes_como_origem_count ?? 0);

        return [
            'id' => $this->id_alocacao,
            'nome' => $this->nome,
            'total_itens_plano' => $totalItensPlano,
            'total_movimentacoes' => $totalMovimentacoes,
            'pode_excluir' => $totalItensPlano === 0 && $totalMovimentacoes === 0,
            'url_atualizar' => route('alocacoes.atualizar', $this->resource),
            'url_excluir' => route('alocacoes.excluir', $this->resource),
        ];
    }
}
