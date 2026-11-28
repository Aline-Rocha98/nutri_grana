<?php

namespace Tests\Feature\Usuario;

use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CriarUsuarioAdministradorDoSistemaCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_cria_novo_usuario_administrador(): void
    {
        $this->artisan('usuario:criar-admin', [
            '--email' => 'admin@exemplo.com',
            '--nome' => 'Admin Sistema',
        ])
            ->expectsQuestion('Senha do administrador', 'senha-segura')
            ->expectsQuestion('Confirme a senha', 'senha-segura')
            ->assertSuccessful();

        $usuario = Usuario::query()->where('email', 'admin@exemplo.com')->first();

        $this->assertNotNull($usuario);
        $this->assertTrue($usuario->ehAdministradorDoSistema());
        $this->assertSame('Admin Sistema', $usuario->nome);
        $this->assertTrue(Hash::check('senha-segura', $usuario->senha));
    }

    public function test_comando_falha_quando_senhas_nao_conferem(): void
    {
        $this->artisan('usuario:criar-admin', [
            '--email' => 'admin@exemplo.com',
            '--nome' => 'Admin Sistema',
        ])
            ->expectsQuestion('Senha do administrador', 'senha-segura')
            ->expectsQuestion('Confirme a senha', 'outra-senha')
            ->assertFailed();

        $this->assertDatabaseMissing('usuario', [
            'email' => 'admin@exemplo.com',
        ]);
    }

    public function test_comando_promove_usuario_existente_a_administrador(): void
    {
        $usuario = Usuario::factory()->create([
            'email' => 'existente@exemplo.com',
            'nome' => 'Usuario Existente',
        ]);

        $this->assertFalse($usuario->ehAdministradorDoSistema());

        $this->artisan('usuario:criar-admin', [
            '--email' => 'existente@exemplo.com',
        ])
            ->expectsConfirmation('Usuário existente@exemplo.com já existe. Deseja promovê-lo a administrador?', 'yes')
            ->expectsConfirmation('Deseja também definir uma nova senha?', 'no')
            ->assertSuccessful();

        $this->assertTrue($usuario->fresh()->ehAdministradorDoSistema());
    }

    public function test_comando_promove_usuario_existente_e_atualiza_senha(): void
    {
        $usuario = Usuario::factory()->create([
            'email' => 'existente@exemplo.com',
        ]);

        $this->artisan('usuario:criar-admin', [
            '--email' => 'existente@exemplo.com',
        ])
            ->expectsConfirmation('Usuário existente@exemplo.com já existe. Deseja promovê-lo a administrador?', 'yes')
            ->expectsConfirmation('Deseja também definir uma nova senha?', 'yes')
            ->expectsQuestion('Senha do administrador', 'nova-senha')
            ->expectsQuestion('Confirme a senha', 'nova-senha')
            ->assertSuccessful();

        $usuarioAtualizado = $usuario->fresh();

        $this->assertTrue($usuarioAtualizado->ehAdministradorDoSistema());
        $this->assertTrue(Hash::check('nova-senha', $usuarioAtualizado->senha));
    }

    public function test_comando_informa_quando_usuario_ja_e_administrador(): void
    {
        Usuario::factory()->administradorDoSistema()->create([
            'email' => 'admin@exemplo.com',
        ]);

        $this->artisan('usuario:criar-admin', [
            '--email' => 'admin@exemplo.com',
        ])
            ->expectsOutputToContain('já é administrador do sistema')
            ->assertSuccessful();
    }
}
