<?php

namespace Database\Factories;

use App\Enum\MotivosControleFinanceiro;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'data_nascimento' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'motivo_controle_financeiro' => MotivosControleFinanceiro::ORGANIZAR_GASTOS->value,
            'remember_token' => Str::random(10),
        ];
    }

    public function comCompartilhamentoHabilitado(): static
    {
        return $this->state(fn () => [
            'compartilhamento_habilitado' => 'S',
        ]);
    }

    public function administradorDoSistema(): static
    {
        return $this->state(fn () => [
            'eh_administrador' => 'S',
        ]);
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Usuario $usuario) {
            $usuario->senha = static::$password ??= 'password';
            $usuario->forceFill(['email_verificado' => 'S']);
        });
    }
}
