<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_recuperacao_de_senha_por_email_esta_desabilitada(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'alguem@example.com'])->assertNotFound();
        $this->get('/reset-password/token-falso')->assertNotFound();
        $this->post('/reset-password', [
            'token' => 'token-falso',
            'email' => 'alguem@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertNotFound();
    }
}
