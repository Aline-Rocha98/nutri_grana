<?php

namespace Database\Factories;

use App\Enum\PapelMembroEspaco;
use App\Enum\StatusEspacoCompartilhado;
use App\Enum\StatusMembroEspaco;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\MembroEspacoCompartilhado;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class EspacoCompartilhadoFactory extends Factory
{
    protected $model = EspacoCompartilhado::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->randomElement(['Casa', 'Viagem', 'Família', 'Apartamento']),
            'criado_por' => null,
            'status' => StatusEspacoCompartilhado::Ativo,
        ];
    }

    public function criadoPorAdministrador(Usuario $administrador): static
    {
        return $this
            ->state(fn () => ['criado_por' => $administrador->id_usuario])
            ->afterCreating(fn (EspacoCompartilhado $espaco) => self::vincularMembroAoEspaco(
                $espaco,
                $administrador,
                PapelMembroEspaco::Administrador
            ));
    }

    public function comMembro(Usuario $membro): static
    {
        return $this->afterCreating(fn (EspacoCompartilhado $espaco) => self::vincularMembroAoEspaco(
            $espaco,
            $membro,
            PapelMembroEspaco::Membro
        ));
    }

    public function arquivado(): static
    {
        return $this->state(fn () => ['status' => StatusEspacoCompartilhado::Arquivado]);
    }

    public static function vincularMembroAoEspaco(
        EspacoCompartilhado $espaco,
        Usuario $usuario,
        PapelMembroEspaco $papel = PapelMembroEspaco::Membro,
    ): MembroEspacoCompartilhado {
        return MembroEspacoCompartilhado::query()->create([
            'id_espaco_compartilhado' => $espaco->id_espaco_compartilhado,
            'id_usuario' => $usuario->id_usuario,
            'papel' => $papel,
            'status' => StatusMembroEspaco::Ativo,
            'entrou_em' => now(),
        ]);
    }
}
