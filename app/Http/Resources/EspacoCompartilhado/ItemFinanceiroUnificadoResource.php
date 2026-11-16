<?php

namespace App\Http\Resources\EspacoCompartilhado;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemFinanceiroUnificadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return is_array($this->resource) ? $this->resource : (array) $this->resource;
    }
}
