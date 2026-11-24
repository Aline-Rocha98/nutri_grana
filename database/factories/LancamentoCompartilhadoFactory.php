<?php

namespace Database\Factories;

use App\Enum\StatusLancamentoCompartilhado;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class LancamentoCompartilhadoFactory extends Factory
{
    protected $model = LancamentoCompartilhado::class;

    public function definition(): array
    {
        return [
            'descricao' => fake()->randomElement(['Aluguel', 'Mercado', 'Internet', 'Energia']),
            'valor' => '150.00',
            'data_vencimento' => now()->toDateString(),
            'status' => StatusLancamentoCompartilhado::Pendente,
            'versao' => 1,
        ];
    }

    public function doEspaco(EspacoCompartilhado $espaco): static
    {
        return $this->state(fn () => [
            'id_espaco_compartilhado' => $espaco->id_espaco_compartilhado,
            'criado_por' => $espaco->criado_por,
        ]);
    }

    public function sobResponsabilidadeDe(Usuario $responsavel): static
    {
        return $this->state(fn () => ['id_usuario_responsavel' => $responsavel->id_usuario]);
    }

    public function comCategoria(int $idCategoria): static
    {
        return $this->state(fn () => ['id_categoria' => $idCategoria]);
    }

    public function pago(): static
    {
        return $this->state(fn () => [
            'status' => StatusLancamentoCompartilhado::Pago,
            'data_pagamento' => now()->toDateString(),
        ]);
    }
}
