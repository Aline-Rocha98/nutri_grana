<?php

namespace Tests\Feature;

use App\Enum\MotivosControleFinanceiro;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = Usuario::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = Usuario::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'nome' => 'Test User',
                'email' => 'test@example.com',
                'data_nascimento' => '1990-05-15',
                'motivo_controle_financeiro' => MotivosControleFinanceiro::ECONOMIZAR_DINHEIRO->value,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->nome);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('1990-05-15', $user->data_nascimento->format('Y-m-d'));
        $this->assertSame(
            MotivosControleFinanceiro::ECONOMIZAR_DINHEIRO->value,
            $user->motivo_controle_financeiro
        );
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = Usuario::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = Usuario::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
