<?php

namespace App\Http\Resources\EspacoCompartilhado;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConviteEspacoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $espaco = $this->relationLoaded('espaco') ? $this->espaco : null;
        $convidado = $this->relationLoaded('convidado') ? $this->convidado : null;

        return [
            'chave' => $this->getRouteKey(),
            'nome_convidado' => $convidado?->nome,
            'email_convidado' => $convidado?->email ?? $this->email_destinatario,
            'expira_em' => $this->expira_em?->toIso8601String(),
            'esta_expirado' => $this->estaExpirado(),
            'criado_por_nome' => $this->relationLoaded('criador') ? $this->criador?->nome : null,
            'url_revogar' => $espaco
                ? route('compartilhados.convites.revogar', [
                    'espacoCompartilhado' => $espaco,
                    'conviteCompartilhamento' => $this->resource,
                ])
                : null,
        ];
    }
}
