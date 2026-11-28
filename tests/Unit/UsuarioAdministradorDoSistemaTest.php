<?php

namespace Tests\Unit;

use App\Enum\SimNao;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UsuarioAdministradorDoSistemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_comum_nao_eh_administrador_do_sistema(): void
    {
        $usuario = Usuario::factory()->create();

        $this->assertFalse($usuario->ehAdministradorDoSistema());
        $this->assertSame(SimNao::Nao, $usuario->eh_administrador);
    }

    public function test_usuario_marcado_como_admin_eh_administrador_do_sistema(): void
    {
        $usuario = Usuario::factory()->administradorDoSistema()->create();

        $this->assertTrue($usuario->ehAdministradorDoSistema());
        $this->assertSame(SimNao::Sim, $usuario->eh_administrador);
    }

    public function test_usuario_da_sessao_eh_administrador_quando_autenticado_como_admin(): void
    {
        $usuario = Usuario::factory()->administradorDoSistema()->create();

        Auth::login($usuario);

        $this->assertTrue(Usuario::usuarioDaSessaoEhAdministrador());
    }

    public function test_usuario_da_sessao_nao_eh_administrador_sem_autenticacao(): void
    {
        $this->assertFalse(Usuario::usuarioDaSessaoEhAdministrador());
    }

    public function test_usuario_da_sessao_nao_eh_administrador_quando_autenticado_como_comum(): void
    {
        $usuario = Usuario::factory()->create();

        Auth::login($usuario);

        $this->assertFalse(Usuario::usuarioDaSessaoEhAdministrador());
    }
}
