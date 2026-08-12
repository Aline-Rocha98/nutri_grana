<?php

namespace App\Http\Resources\Objetivo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AporteObjetivoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_aporte_objetivo,
            'tipo' => $this->tipo?->value,
            'tipo_rotulo' => $this->tipo?->rotulo(),
            'valor' => number_format((float) $this->valor, 2, ',', '.'),
            'valor_numero' => (float) $this->valor,
            'data_aporte' => $this->data_aporte?->format('Y-m-d'),
            'data_aporte_formatada' => $this->data_aporte?->format('d/m/Y'),
            'id_conta_bancaria' => $this->id_conta_bancaria,
            'conta_bancaria_nome' => $this->contaBancaria?->nome,
            'observacao' => $this->observacao,
        ];
    }
}
