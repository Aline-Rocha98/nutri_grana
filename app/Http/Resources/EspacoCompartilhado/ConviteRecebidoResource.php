<?php

namespace App\Http\Resources\EspacoCompartilhado;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConviteRecebidoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'chave' => $this->getRouteKey(),
            'nome_espaco' => $this->relationLoaded('espaco') ? $this->espaco?->nome : null,
            'nome_convidante' => $this->relationLoaded('criador') ? $this->criador?->nome : null,
            'expira_em' => $this->expira_em?->toIso8601String(),
            'esta_expirado' => $this->estaExpirado(),
            'url_aceitar' => route('compartilhados.convites.aceitar', ['conviteCompartilhamento' => $this->resource]),
            'url_recusar' => route('compartilhados.convites.recusar', ['conviteCompartilhamento' => $this->resource]),
        ];
    }
}
