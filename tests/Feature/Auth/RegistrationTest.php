<?php

namespace Tests\Feature\Auth;

use App\Enum\MotivosControleFinanceiro;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/registro');

        $response
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Acesso')
                ->where('modoInicial', 'cadastro')
                ->has('motivos', count(MotivosControleFinanceiro::cases()))
            );
    }

    public function test_old_register_url_no_longer_exists(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/registro', [
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

        $response = $this->from('/registro')->post('/registro', [
            'nome' => 'Outro Usuario',
            'email' => 'test@example.com',
            'data_nascimento' => '1990-01-15',
            'motivo_controle_financeiro' => 'Organizar gastos',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertRedirect('/registro')
            ->assertSessionHasErrors([
                'email' => __('validation.usuario.email.unique'),
            ]);

        $this->assertGuest();
    }
}
