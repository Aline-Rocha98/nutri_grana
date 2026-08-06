<?php

namespace Database\Factories;

use App\Enum\MotivosControleFinanceiro;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'senha' => static::$password ??= 'password',
            'email_verificado' => 'S',
            'data_nascimento' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'motivo_controle_financeiro' => MotivosControleFinanceiro::ORGANIZAR_GASTOS->value,
            'remember_token' => Str::random(10),
        ];
    }
}
