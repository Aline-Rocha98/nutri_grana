<?php

namespace App\Http\Resources\EspacoCompartilhado;

use App\Support\Data\IniciaisNome;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembroEspacoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $usuario = $this->usuario;
        $idUsuario = (int) $this->id_usuario;
        $espaco = $this->relationLoaded('espaco') ? $this->espaco : null;

        return [
            'chave' => $this->getRouteKey(),
            'id_usuario' => $idUsuario,
            'nome' => $usuario?->nome ?? 'Membro',
            'iniciais' => IniciaisNome::extrairIniciais($usuario?->nome, '?'),
            'foto_url' => $usuario?->foto_url,
            'papel' => $this->papel?->value,
            'papel_rotulo' => $this->papel?->rotulo(),
            'entrou_em_formatado' => $this->entrou_em?->format('d/m/Y'),
            'eh_usuario_atual' => $idUsuario === (int) $request->user()?->getAuthIdentifier(),
            'url_categorias' => $espaco
                ? route('compartilhados.membros.categorias', ['espacoCompartilhado' => $espaco, 'membroEspaco' => $this->resource])
                : null,
            'url_remover' => $espaco
                ? route('compartilhados.membros.remover', ['espacoCompartilhado' => $espaco, 'membroEspaco' => $this->resource])
                : null,
        ];
    }
}
