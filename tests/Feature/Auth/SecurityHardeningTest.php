<?php

namespace Tests\Feature\Auth;

use App\Enum\MotivosControleFinanceiro;
use App\Http\Middleware\EnsureAbsoluteSessionTimeout;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        $user = Usuario::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringContainsString(
            'Muitas tentativas',
            collect(session('errors')->get('email'))->first() ?? ''
        );
    }

    public function test_absolute_session_timeout_logs_user_out(): void
    {
        config(['session.absolute_lifetime' => 1]);

        $user = Usuario::factory()->create();

        $this->actingAs($user);
        session([EnsureAbsoluteSessionTimeout::SESSION_LOGIN_AT => now()->subMinutes(2)->getTimestamp()]);

        $response = $this->get(route('home', absolute: false));

        $response->assertRedirect(route('login', absolute: false));
        $this->assertGuest();
    }

    public function test_password_is_hashed_with_configured_driver(): void
    {
        $user = new Usuario([
            'nome' => 'Teste',
            'email' => 'hash@example.com',
            'data_nascimento' => '1990-01-01',
            'motivo_controle_financeiro' => MotivosControleFinanceiro::ORGANIZAR_GASTOS->value,
        ]);
        $user->senha = 'secret-password-123';
        $user->forceFill(['email_verificado' => 'S']);
        $user->save();

        $this->assertTrue(Hash::check('secret-password-123', $user->fresh()->senha));
        $this->assertNotSame('secret-password-123', $user->fresh()->senha);
    }

    public function test_senha_and_email_verificado_are_not_mass_assignable(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\MassAssignmentException::class);

        $user = new Usuario;
        $user->fill([
            'nome' => 'Mass',
            'email' => 'mass@example.com',
            'senha' => 'should-be-ignored',
            'email_verificado' => 'S',
        ]);
    }
}
