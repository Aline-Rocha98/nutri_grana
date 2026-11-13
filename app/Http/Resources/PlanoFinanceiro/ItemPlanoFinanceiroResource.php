<?php

namespace App\Http\Resources\PlanoFinanceiro;

use App\Enum\TipoCalculoItemPlano;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemPlanoFinanceiroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ehPercentual = $this->tipo_calculo === TipoCalculoItemPlano::Percentual;
        $contaDestino = $this->relationLoaded('contaBancariaDestino') ? $this->contaBancariaDestino : null;
        $objetivo = $this->relationLoaded('objetivo') ? $this->objetivo : null;
        $alocacao = $this->relationLoaded('alocacao') ? $this->alocacao : null;

        return [
            'id' => $this->id_item_plano,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'cor' => $this->cor?->value,
            'cor_hexadecimal' => $this->cor?->corHexadecimal(),
            'tipo_calculo' => $this->tipo_calculo?->value,
            'tipo_calculo_rotulo' => $this->tipo_calculo?->rotulo(),
            'valor' => number_format((float) $this->valor, 2, ',', '.'),
            'valor_numero' => (float) $this->valor,
            'regra_formatada' => $ehPercentual
                ? rtrim(rtrim(number_format((float) $this->valor, 2, ',', '.'), '0'), ',').'%'
                : 'R$ '.number_format((float) $this->valor, 2, ',', '.'),
            'separar_do_saldo' => $this->separaDoSaldo(),
            'id_conta_bancaria_destino' => $this->id_conta_bancaria_destino,
            'conta_bancaria_destino_nome' => $contaDestino?->nome,
            'id_objetivo' => $this->id_objetivo,
            'objetivo_descricao' => $objetivo?->descricao,
            'id_alocacao' => $this->id_alocacao,
            'alocacao_nome' => $alocacao?->nome,
        ];
    }
}
