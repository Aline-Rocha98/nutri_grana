<?php

namespace App\Http\Resources\Usuario;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $iniciais = collect(explode(' ', $this->nome ?? ''))
            ->filter()
            ->take(2)
            ->map(fn (string $parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');

        return [
            'id' => $this->id_usuario,
            'nome' => $this->nome,
            'email' => $this->email,
            'data_nascimento' => $this->data_nascimento?->format('Y-m-d'),
            'motivo_controle_financeiro' => $this->motivo_controle_financeiro,
            'foto_url' => $this->foto_url,
            'iniciais' => $iniciais ?: 'NG',
        ];
    }
}
