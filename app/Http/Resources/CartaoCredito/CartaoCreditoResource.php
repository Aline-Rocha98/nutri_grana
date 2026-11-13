<?php

namespace App\Http\Resources\CartaoCredito;

use App\Data\BancosSugeridos;
use App\Enum\AcabamentoVisual;
use App\Support\Data\FormatarDia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartaoCreditoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $temFaturaAberta = (bool) ($this->tem_fatura_aberta ?? false);
        $limiteDisponivel = (float) ($this->limite_disponivel ?? $this->limite_total);
        $limiteUsado = (float) ($this->limite_usado ?? 0);
        $acabamento = $this->acabamento ?? AcabamentoVisual::padrao();

        return [
            'id' => $this->id_cartao_credito,
            'chave' => $this->getRouteKey(),
            'nome' => $this->nome,
            'nome_titular' => $this->nome_titular,
            'ultimos_digitos' => $this->ultimos_digitos,
            'limite_total' => number_format((float) $this->limite_total, 2, ',', '.'),
            'limite_total_numero' => (float) $this->limite_total,
            'limite_disponivel' => number_format($limiteDisponivel, 2, ',', '.'),
            'limite_disponivel_numero' => $limiteDisponivel,
            'limite_usado' => number_format($limiteUsado, 2, ',', '.'),
            'limite_usado_numero' => $limiteUsado,
            'percentual_utilizado' => (float) ($this->percentual_utilizado ?? 0),
            'dia_fechamento' => (int) $this->dia_fechamento,
            'dia_fechamento_formatado' => FormatarDia::pad($this->dia_fechamento),
            'dia_vencimento' => (int) $this->dia_vencimento,
            'dia_vencimento_formatado' => FormatarDia::pad($this->dia_vencimento),
            'bandeira' => $this->bandeira?->value,
            'bandeira_rotulo' => $this->bandeira?->rotulo(),
            'acabamento' => $acabamento->value,
            'acabamento_cor' => $acabamento->corDestaque(),
            'padrao' => $this->padrao?->value,
            'arquivada' => $this->arquivada?->value,
            'tem_fatura_aberta' => $temFaturaAberta,
            'pode_excluir' => ! $temFaturaAberta,
            'logo' => BancosSugeridos::logoPorNome($this->nome),
            'url_atualizar' => route('cartoes-credito.atualizar', $this->resource),
            'url_arquivar' => route('cartoes-credito.arquivar', $this->resource),
            'url_definir_padrao' => route('cartoes-credito.definir-padrao', $this->resource),
            'url_excluir' => route('cartoes-credito.excluir', $this->resource),
            'url_faturas' => route('faturas-cartao.index', $this->resource),
        ];
    }
}
