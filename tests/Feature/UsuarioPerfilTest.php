<?php

namespace Tests\Feature;

use App\Enum\MotivosControleFinanceiro;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioPerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_perfil_e_exibida(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->get('/usuario/perfil');

        $response->assertOk();
    }

    public function test_perfil_pode_ser_atualizado(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->patch('/usuario', [
                'nome' => 'Test User',
                'email' => 'test@example.com',
                'data_nascimento' => '1990-05-15',
                'motivo_controle_financeiro' => MotivosControleFinanceiro::ECONOMIZAR_DINHEIRO->value,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/usuario/perfil');

        $usuario->refresh();

        $this->assertSame('Test User', $usuario->nome);
        $this->assertSame('test@example.com', $usuario->email);
        $this->assertSame('1990-05-15', $usuario->data_nascimento->format('Y-m-d'));
        $this->assertSame(
            MotivosControleFinanceiro::ECONOMIZAR_DINHEIRO->value,
            $usuario->motivo_controle_financeiro
        );
    }

    public function test_usuario_pode_excluir_a_conta(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->delete('/usuario', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($usuario->fresh());
    }

    public function test_senha_correta_e_obrigatoria_para_excluir_conta(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->from('/usuario/perfil')
            ->delete('/usuario', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/usuario/perfil');

        $this->assertNotNull($usuario->fresh());
    }
}
