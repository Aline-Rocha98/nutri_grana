<?php

namespace Tests\Feature\Auth;

use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'nome' => 'Test User',
            'email' => 'test@example.com',
            'data_nascimento' => '1990-01-15',
            'motivo_controle_financeiro' => 'Organizar gastos',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        Usuario::factory()->create(['email' => 'test@example.com']);

        $response = $this->from('/register')->post('/register', [
            'nome' => 'Outro Usuario',
            'email' => 'test@example.com',
            'data_nascimento' => '1990-01-15',
            'motivo_controle_financeiro' => 'Organizar gastos',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors([
                'email' => __('validation.usuario.email.unique'),
            ]);

        $this->assertGuest();
    }
}
