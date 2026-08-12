<?php

namespace Tests\Feature;

use App\Enum\SimNao;
use App\Enum\TipoAporteObjetivo;
use App\Enum\TipoContaBancaria;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Objetivo\AporteObjetivo;
use App\Models\Objetivo\Objetivo;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObjetivoTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_criar_objetivo(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->post('/objetivos', [
                'descricao' => 'Viagem para a praia',
                'valor_meta' => '5.000,00',
                'data_limite' => now()->addMonths(10)->toDateString(),
                'exibir_dashboard' => 'S',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/objetivos');

        $this->assertDatabaseHas('objetivos', [
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Viagem para a praia',
            'valor_meta' => 5000.00,
            'exibir_dashboard' => 'S',
        ]);
    }

    public function test_usuario_pode_registrar_aporte_manual(): void
    {
        $usuario = Usuario::factory()->create();
        $objetivo = $this->criarObjetivo($usuario);

        $response = $this
            ->actingAs($usuario)
            ->post('/objetivos/'.$objetivo->getRouteKey().'/aportes', [
                'tipo' => TipoAporteObjetivo::Manual->value,
                'valor' => '250,00',
                'data_aporte' => now()->toDateString(),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/objetivos');

        $this->assertDatabaseHas('aportes_objetivo', [
            'id_objetivo' => $objetivo->id_objetivo,
            'id_usuario' => $usuario->id_usuario,
            'tipo' => TipoAporteObjetivo::Manual->value,
            'valor' => 250.00,
        ]);
    }

    public function test_aporte_com_conta_bancaria_cria_lancamento_e_debita_saldo(): void
    {
        $usuario = Usuario::factory()->create();
        $objetivo = $this->criarObjetivo($usuario, 1000);
        $conta = $this->criarConta($usuario, 800);

        $response = $this
            ->actingAs($usuario)
            ->post('/objetivos/'.$objetivo->getRouteKey().'/aportes', [
                'tipo' => TipoAporteObjetivo::ContaBancaria->value,
                'valor' => '200,00',
                'data_aporte' => now()->toDateString(),
                'id_conta_bancaria' => $conta->id_conta_bancaria,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/objetivos');

        $this->assertDatabaseHas('aportes_objetivo', [
            'id_objetivo' => $objetivo->id_objetivo,
            'tipo' => TipoAporteObjetivo::ContaBancaria->value,
            'valor' => 200.00,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
        ]);

        $this->assertDatabaseHas('lancamentos', [
            'id_usuario' => $usuario->id_usuario,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'tipo' => 'despesa',
            'situacao' => 'pago',
            'valor' => 200.00,
        ]);

        $this->assertTrue(
            AporteObjetivo::query()
                ->where('id_objetivo', $objetivo->id_objetivo)
                ->whereNotNull('id_lancamento')
                ->exists()
        );
    }

    public function test_nao_permite_aporte_maior_que_saldo_da_conta(): void
    {
        $usuario = Usuario::factory()->create();
        $objetivo = $this->criarObjetivo($usuario);
        $conta = $this->criarConta($usuario, 50);

        $response = $this
            ->actingAs($usuario)
            ->from('/objetivos')
            ->post('/objetivos/'.$objetivo->getRouteKey().'/aportes', [
                'tipo' => TipoAporteObjetivo::ContaBancaria->value,
                'valor' => '100,00',
                'data_aporte' => now()->toDateString(),
                'id_conta_bancaria' => $conta->id_conta_bancaria,
            ]);

        $response->assertSessionHasErrors('valor');
        $this->assertDatabaseCount('aportes_objetivo', 0);
        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_usuario_nao_acessa_objetivo_de_outro(): void
    {
        $dono = Usuario::factory()->create();
        $outro = Usuario::factory()->create();
        $objetivo = $this->criarObjetivo($dono);

        $this
            ->actingAs($outro)
            ->put('/objetivos/'.$objetivo->getRouteKey(), [
                'descricao' => 'Tentativa',
                'valor_meta' => '100,00',
                'data_limite' => now()->addYear()->toDateString(),
                'exibir_dashboard' => 'N',
            ])
            ->assertForbidden();
    }

    public function test_listagem_mostra_progresso_calculado(): void
    {
        $usuario = Usuario::factory()->create();
        $objetivo = $this->criarObjetivo($usuario, 1000);

        AporteObjetivo::query()->create([
            'id_objetivo' => $objetivo->id_objetivo,
            'id_usuario' => $usuario->id_usuario,
            'tipo' => TipoAporteObjetivo::Manual,
            'valor' => 250,
            'data_aporte' => now()->toDateString(),
        ]);

        $response = $this
            ->actingAs($usuario)
            ->get('/objetivos');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Objetivo/Index')
            ->has('objetivos', 1)
            ->where('objetivos.0.percentual_atual', 25)
            ->where('objetivos.0.valor_guardado_numero', 250)
            ->where('objetivos.0.valor_faltante_numero', 750)
        );
    }

    private function criarObjetivo(Usuario $usuario, float $valorMeta = 5000): Objetivo
    {
        return Objetivo::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Objetivo de teste',
            'valor_meta' => $valorMeta,
            'data_limite' => now()->addMonths(6)->toDateString(),
            'exibir_dashboard' => SimNao::Nao,
        ]);
    }

    private function criarConta(Usuario $usuario, float $saldoInicial): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Conta teste',
            'saldo_inicial' => $saldoInicial,
            'tipo' => TipoContaBancaria::Corrente,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Nao,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }
}
