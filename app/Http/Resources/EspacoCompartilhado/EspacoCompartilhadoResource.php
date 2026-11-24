<?php

namespace App\Http\Resources\EspacoCompartilhado;

use App\Support\Data\IniciaisNome;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EspacoCompartilhadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $membros = $this->relationLoaded('membrosAtivos')
            ? $this->membrosAtivos->map(fn ($membro) => [
                'id_usuario' => (int) $membro->id_usuario,
                'nome' => $membro->usuario?->nome ?? 'Membro',
                'iniciais' => IniciaisNome::extrairIniciais($membro->usuario?->nome, '?'),
                'foto_url' => $membro->usuario?->foto_url,
            ])->values()->all()
            : [];

        return [
            'chave' => $this->getRouteKey(),
            'nome' => $this->nome,
            'status' => $this->status?->value,
            'quantidade_membros' => (int) ($this->membros_ativos_count ?? count($membros)),
            'membros' => $membros,
            'url_painel' => route('compartilhados.show', ['espacoCompartilhado' => $this->resource]),
            'url_atualizar' => route('compartilhados.atualizar', ['espacoCompartilhado' => $this->resource]),
            'url_arquivar' => route('compartilhados.arquivar', ['espacoCompartilhado' => $this->resource]),
            'url_sair' => route('compartilhados.sair', ['espacoCompartilhado' => $this->resource]),
        ];
    }
}
