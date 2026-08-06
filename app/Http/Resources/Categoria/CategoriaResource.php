<?php

namespace App\Http\Resources\Categoria;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalLancamentos = (int) ($this->total_lancamentos ?? 0);

        return [
            'id' => $this->id_categoria,
            'id_categoria_pai' => $this->id_categoria_pai,
            'nivel' => $this->nivel,
            'nome' => $this->nome,
            'tipo' => $this->tipo?->value,
            'tipo_rotulo' => $this->tipo?->rotulo(),
            'icone' => $this->icone,
            'cor' => $this->cor,
            'padrao' => $this->padrao?->value,
            'arquivada' => $this->arquivada?->value,
            'total_lancamentos' => $totalLancamentos,
            'pode_excluir' => $totalLancamentos === 0,
            'url_atualizar' => route('categorias.atualizar', $this->resource),
            'url_arquivar' => route('categorias.arquivar', $this->resource),
            'url_excluir' => route('categorias.excluir', $this->resource),
            'subcategorias' => $this->whenLoaded(
                'subcategorias',
                fn () => CategoriaResource::collection($this->subcategorias)->resolve()
            ),
        ];
    }
}
