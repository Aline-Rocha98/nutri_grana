<?php

namespace Tests\Feature;

use App\Enum\CorItemPlanoFinanceiro;
use App\Enum\SimNao;
use App\Enum\SituacaoPlanoFinanceiro;
use App\Enum\TipoCalculoItemPlano;
use App\Enum\TipoContaBancaria;
use App\Enum\TipoMovimentacaoSaldoGuardado;
use App\Models\Alocacao\Alocacao;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\ContaBancaria\MovimentacaoSaldoGuardado;
use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use App\Models\PlanoFinanceiro\PlanoFinanceiro;
use App\Models\Usuario\Usuario;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Repositories\ContaBancaria\MovimentacaoSaldoGuardadoRepository;
use App\Services\Alocacao\ConsolidadorSaldoAlocacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlocacaoTest extends TestCase
{
    use RefreshDatabase;

    private const URL_ALOCACOES = '/alocacoes';

    public function test_tela_de_alocacoes_lista_saldo_e_cadastradas(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $alocacaoCarro = $this->criarAlocacao($usuario, 'Carro');
        $this->criarAlocacao($usuario, 'Viagem');
        $this->registrarSeparacao($usuario, $alocacaoCarro, $conta, 300);

        $this->actingAs($usuario)
            ->get(self::URL_ALOCACOES)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Alocacao/Index')
                ->has('alocacoes', 2)
                ->where('alocacoes.0.nome', 'Carro')
                ->where('alocacoes.0.saldo_numero', 300.0)
                ->where('alocacoes.0.saldos_por_conta.0.id_conta_bancaria', (int) $conta->id_conta_bancaria)
                ->where('totalAlocado', 300.0)
                ->has('alocacoesCadastradas.data', 2)
            );
    }

    public function test_usuario_pode_criar_alocacao(): void
    {
        $usuario = Usuario::factory()->create();

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->post(self::URL_ALOCACOES, ['nome' => '  Reserva   de emergência '])
            ->assertSessionHasNoErrors()
            ->assertRedirect(self::URL_ALOCACOES);

        $this->assertDatabaseHas('alocacoes', [
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Reserva de emergência',
            'saldo_consolidado' => 0,
        ]);
    }

    public function test_nao_permite_alocacao_com_nome_duplicado(): void
    {
        $usuario = Usuario::factory()->create();
        $this->criarAlocacao($usuario, 'Carro');

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->post(self::URL_ALOCACOES, ['nome' => 'Carro'])
            ->assertSessionHasErrors('nome');

        $this->assertDatabaseCount('alocacoes', 1);
    }

    public function test_usuarios_diferentes_podem_ter_alocacoes_com_mesmo_nome(): void
    {
        $outroUsuario = Usuario::factory()->create();
        $this->criarAlocacao($outroUsuario, 'Carro');
        $usuario = Usuario::factory()->create();

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->post(self::URL_ALOCACOES, ['nome' => 'Carro'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('alocacoes', 2);
    }

    public function test_usuario_pode_renomear_alocacao(): void
    {
        $usuario = Usuario::factory()->create();
        $alocacao = $this->criarAlocacao($usuario, 'Carro');

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->put(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey(), ['nome' => 'Carro novo'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('alocacoes', [
            'id_alocacao' => $alocacao->id_alocacao,
            'nome' => 'Carro novo',
        ]);
    }

    public function test_nao_permite_excluir_alocacao_em_uso_no_plano(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $alocacao = $this->criarAlocacao($usuario, 'Carro');
        $this->criarItemPlanoComAlocacao($usuario, $alocacao, $conta);

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->delete(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey())
            ->assertSessionHas('erro');

        $this->assertDatabaseHas('alocacoes', ['id_alocacao' => $alocacao->id_alocacao]);
    }

    public function test_nao_permite_excluir_alocacao_com_movimentacoes(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $alocacao = $this->criarAlocacao($usuario, 'Carro');
        $this->registrarSeparacao($usuario, $alocacao, $conta, 100);

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->delete(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey())
            ->assertSessionHas('erro');

        $this->assertDatabaseHas('alocacoes', ['id_alocacao' => $alocacao->id_alocacao]);
    }

    public function test_usuario_pode_excluir_alocacao_sem_vinculos(): void
    {
        $usuario = Usuario::factory()->create();
        $alocacao = $this->criarAlocacao($usuario, 'Carro');

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->delete(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey())
            ->assertSessionHas('sucesso');

        $this->assertDatabaseMissing('alocacoes', ['id_alocacao' => $alocacao->id_alocacao]);
    }

    public function test_transferencia_entre_alocacoes_move_saldo_sem_alterar_a_conta(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 1000);
        $alocacaoCarro = $this->criarAlocacao($usuario, 'Carro');
        $alocacaoViagem = $this->criarAlocacao($usuario, 'Viagem');
        $this->registrarSeparacao($usuario, $alocacaoCarro, $conta, 500);

        $repositorio = app(ContaBancariaRepository::class);
        $saldoGuardadoAntes = $repositorio->saldoGuardadoDaConta($conta->fresh());
        $saldoTotalAntes = $repositorio->saldoTotalDaConta($conta->fresh());

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->post(self::URL_ALOCACOES.'/'.$alocacaoCarro->getRouteKey().'/transferir', [
                'id_alocacao_destino' => $alocacaoViagem->id_alocacao,
                'id_conta_bancaria' => $conta->id_conta_bancaria,
                'valor' => '200,00',
                'data_transferencia' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(self::URL_ALOCACOES);

        $this->assertDatabaseHas('movimentacoes_saldo_guardado', [
            'tipo' => TipoMovimentacaoSaldoGuardado::Transferencia->value,
            'id_alocacao' => $alocacaoViagem->id_alocacao,
            'id_alocacao_origem' => $alocacaoCarro->id_alocacao,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'valor' => 200.00,
        ]);

        $this->assertDatabaseHas('alocacoes', ['id_alocacao' => $alocacaoCarro->id_alocacao, 'saldo_consolidado' => 300.00]);
        $this->assertDatabaseHas('alocacoes', ['id_alocacao' => $alocacaoViagem->id_alocacao, 'saldo_consolidado' => 200.00]);

        $this->assertSame($saldoGuardadoAntes, $repositorio->saldoGuardadoDaConta($conta->fresh()));
        $this->assertSame($saldoTotalAntes, $repositorio->saldoTotalDaConta($conta->fresh()));
    }

    public function test_transferencia_nao_permite_valor_maior_que_saldo_na_conta(): void
    {
        $usuario = Usuario::factory()->create();
        $contaA = $this->criarConta($usuario, 0, 'Conta A');
        $contaB = $this->criarConta($usuario, 0, 'Conta B');
        $alocacaoCarro = $this->criarAlocacao($usuario, 'Carro');
        $alocacaoViagem = $this->criarAlocacao($usuario, 'Viagem');
        $this->registrarSeparacao($usuario, $alocacaoCarro, $contaA, 100);
        $this->registrarSeparacao($usuario, $alocacaoCarro, $contaB, 400);

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->post(self::URL_ALOCACOES.'/'.$alocacaoCarro->getRouteKey().'/transferir', [
                'id_alocacao_destino' => $alocacaoViagem->id_alocacao,
                'id_conta_bancaria' => $contaA->id_conta_bancaria,
                'valor' => '150,00',
                'data_transferencia' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('valor');

        $this->assertDatabaseMissing('movimentacoes_saldo_guardado', [
            'tipo' => TipoMovimentacaoSaldoGuardado::Transferencia->value,
        ]);
    }

    public function test_transferencia_nao_permite_destino_igual_a_origem(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $alocacao = $this->criarAlocacao($usuario, 'Carro');
        $this->registrarSeparacao($usuario, $alocacao, $conta, 100);

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->post(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey().'/transferir', [
                'id_alocacao_destino' => $alocacao->id_alocacao,
                'id_conta_bancaria' => $conta->id_conta_bancaria,
                'valor' => '50,00',
                'data_transferencia' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('id_alocacao_destino');
    }

    public function test_resgate_para_a_mesma_conta_libera_saldo_disponivel(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 1000);
        $alocacao = $this->criarAlocacao($usuario, 'Carro');
        $this->registrarSeparacao($usuario, $alocacao, $conta, 400);

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->post(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey().'/resgatar', [
                'id_conta_bancaria_saida' => $conta->id_conta_bancaria,
                'id_conta_bancaria_destino' => $conta->id_conta_bancaria,
                'valor' => '150,00',
                'data_resgate' => now()->toDateString(),
                'observacao' => 'Conserto do carro',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(self::URL_ALOCACOES);

        $this->assertDatabaseHas('movimentacoes_saldo_guardado', [
            'tipo' => TipoMovimentacaoSaldoGuardado::Resgate->value,
            'id_alocacao' => $alocacao->id_alocacao,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'id_conta_bancaria_resgate' => null,
            'valor' => 150.00,
            'observacao' => 'Conserto do carro',
        ]);
        $this->assertDatabaseHas('alocacoes', ['id_alocacao' => $alocacao->id_alocacao, 'saldo_consolidado' => 250.00]);

        $repositorio = app(ContaBancariaRepository::class);
        $contaAtualizada = $conta->fresh();

        $this->assertSame(1400.0, $repositorio->saldoTotalDaConta($contaAtualizada));
        $this->assertSame(250.0, $repositorio->saldoGuardadoDaConta($contaAtualizada));
        $this->assertSame(1150.0, $repositorio->saldoDisponivelDaConta($contaAtualizada));
    }

    public function test_resgate_para_outra_conta_move_o_valor_entre_as_contas(): void
    {
        $usuario = Usuario::factory()->create();
        $contaReserva = $this->criarConta($usuario, 0, 'Conta reserva');
        $contaCorrente = $this->criarConta($usuario, 0, 'Conta corrente');
        $alocacao = $this->criarAlocacao($usuario, 'Carro');
        $this->registrarSeparacao($usuario, $alocacao, $contaReserva, 500);

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->post(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey().'/resgatar', [
                'id_conta_bancaria_saida' => $contaReserva->id_conta_bancaria,
                'id_conta_bancaria_destino' => $contaCorrente->id_conta_bancaria,
                'valor' => '200,00',
                'data_resgate' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('movimentacoes_saldo_guardado', [
            'tipo' => TipoMovimentacaoSaldoGuardado::Resgate->value,
            'id_conta_bancaria' => $contaReserva->id_conta_bancaria,
            'id_conta_bancaria_resgate' => $contaCorrente->id_conta_bancaria,
            'valor' => 200.00,
        ]);

        $repositorio = app(ContaBancariaRepository::class);
        $contaReservaAtualizada = $contaReserva->fresh();
        $contaCorrenteAtualizada = $contaCorrente->fresh();

        $this->assertSame(300.0, $repositorio->saldoTotalDaConta($contaReservaAtualizada));
        $this->assertSame(300.0, $repositorio->saldoGuardadoDaConta($contaReservaAtualizada));
        $this->assertSame(0.0, $repositorio->saldoDisponivelDaConta($contaReservaAtualizada));

        $this->assertSame(200.0, $repositorio->saldoTotalDaConta($contaCorrenteAtualizada));
        $this->assertSame(0.0, $repositorio->saldoGuardadoDaConta($contaCorrenteAtualizada));
        $this->assertSame(200.0, $repositorio->saldoDisponivelDaConta($contaCorrenteAtualizada));
    }

    public function test_resgate_nao_permite_valor_maior_que_saldo_da_alocacao_na_conta(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $alocacao = $this->criarAlocacao($usuario, 'Carro');
        $this->registrarSeparacao($usuario, $alocacao, $conta, 100);

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->post(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey().'/resgatar', [
                'id_conta_bancaria_saida' => $conta->id_conta_bancaria,
                'id_conta_bancaria_destino' => $conta->id_conta_bancaria,
                'valor' => '100,01',
                'data_resgate' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('valor');

        $this->assertDatabaseMissing('movimentacoes_saldo_guardado', [
            'tipo' => TipoMovimentacaoSaldoGuardado::Resgate->value,
        ]);
    }

    public function test_resgate_nao_permite_conta_destino_arquivada(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $contaArquivada = $this->criarConta($usuario, 0, 'Arquivada', SimNao::Sim);
        $alocacao = $this->criarAlocacao($usuario, 'Carro');
        $this->registrarSeparacao($usuario, $alocacao, $conta, 100);

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->post(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey().'/resgatar', [
                'id_conta_bancaria_saida' => $conta->id_conta_bancaria,
                'id_conta_bancaria_destino' => $contaArquivada->id_conta_bancaria,
                'valor' => '50,00',
                'data_resgate' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('id_conta_bancaria_destino');
    }

    public function test_saldo_consolidado_corresponde_ao_historico_de_movimentacoes(): void
    {
        $usuario = Usuario::factory()->create();
        $contaA = $this->criarConta($usuario, 0, 'Conta A');
        $contaB = $this->criarConta($usuario, 0, 'Conta B');
        $alocacaoCarro = $this->criarAlocacao($usuario, 'Carro');
        $alocacaoViagem = $this->criarAlocacao($usuario, 'Viagem');
        $this->registrarSeparacao($usuario, $alocacaoCarro, $contaA, 600);
        $this->registrarSeparacao($usuario, $alocacaoCarro, $contaB, 400);

        $this->actingAs($usuario)
            ->post(self::URL_ALOCACOES.'/'.$alocacaoCarro->getRouteKey().'/transferir', [
                'id_alocacao_destino' => $alocacaoViagem->id_alocacao,
                'id_conta_bancaria' => $contaA->id_conta_bancaria,
                'valor' => '250,00',
                'data_transferencia' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($usuario)
            ->post(self::URL_ALOCACOES.'/'.$alocacaoCarro->getRouteKey().'/resgatar', [
                'id_conta_bancaria_saida' => $contaB->id_conta_bancaria,
                'id_conta_bancaria_destino' => $contaA->id_conta_bancaria,
                'valor' => '100,00',
                'data_resgate' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $repositorioMovimentacoes = app(MovimentacaoSaldoGuardadoRepository::class);

        $this->assertSame(650.0, $repositorioMovimentacoes->somarSaldoDaAlocacao((int) $alocacaoCarro->id_alocacao));
        $this->assertSame(250.0, $repositorioMovimentacoes->somarSaldoDaAlocacao((int) $alocacaoViagem->id_alocacao));

        $saldosCarroPorConta = $repositorioMovimentacoes->somarSaldoDaAlocacaoPorConta((int) $alocacaoCarro->id_alocacao);
        $this->assertSame(350.0, $saldosCarroPorConta[(int) $contaA->id_conta_bancaria]);
        $this->assertSame(300.0, $saldosCarroPorConta[(int) $contaB->id_conta_bancaria]);

        $this->assertSame(650.0, (float) $alocacaoCarro->fresh()->saldo_consolidado);
        $this->assertSame(250.0, (float) $alocacaoViagem->fresh()->saldo_consolidado);
    }

    public function test_reconsolidacao_corrige_cache_divergente(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $alocacao = $this->criarAlocacao($usuario, 'Carro');
        $this->registrarSeparacao($usuario, $alocacao, $conta, 320);

        Alocacao::query()->whereKey($alocacao->id_alocacao)->update(['saldo_consolidado' => 9999]);

        $this->artisan('alocacoes:reconsolidar-saldos')->assertSuccessful();

        $this->assertSame(320.0, (float) $alocacao->fresh()->saldo_consolidado);
    }

    public function test_usuario_nao_pode_movimentar_alocacao_de_outro_usuario(): void
    {
        $dono = Usuario::factory()->create();
        $intruso = Usuario::factory()->create();
        $conta = $this->criarConta($dono);
        $alocacao = $this->criarAlocacao($dono, 'Carro');
        $this->registrarSeparacao($dono, $alocacao, $conta, 100);

        $this->actingAs($intruso)
            ->post(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey().'/resgatar', [
                'id_conta_bancaria_saida' => $conta->id_conta_bancaria,
                'id_conta_bancaria_destino' => $conta->id_conta_bancaria,
                'valor' => '10,00',
                'data_resgate' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($intruso)
            ->put(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey(), ['nome' => 'Hackeado'])
            ->assertForbidden();

        $this->actingAs($intruso)
            ->delete(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey())
            ->assertForbidden();
    }

    public function test_transferencia_nao_aceita_destino_de_outro_usuario(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $alocacao = $this->criarAlocacao($usuario, 'Carro');
        $alocacaoDeOutroUsuario = $this->criarAlocacao(Usuario::factory()->create(), 'Viagem');
        $this->registrarSeparacao($usuario, $alocacao, $conta, 100);

        $this->actingAs($usuario)
            ->from(self::URL_ALOCACOES)
            ->post(self::URL_ALOCACOES.'/'.$alocacao->getRouteKey().'/transferir', [
                'id_alocacao_destino' => $alocacaoDeOutroUsuario->id_alocacao,
                'id_conta_bancaria' => $conta->id_conta_bancaria,
                'valor' => '50,00',
                'data_transferencia' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('id_alocacao_destino');
    }

    private function criarConta(
        Usuario $usuario,
        float $saldoInicial = 0,
        string $nome = 'Conta teste',
        SimNao $arquivada = SimNao::Nao,
    ): ContaBancaria {
        return ContaBancaria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => $nome,
            'saldo_inicial' => $saldoInicial,
            'tipo' => TipoContaBancaria::Corrente,
            'arquivada' => $arquivada,
            'padrao_desconto' => SimNao::Nao,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }

    private function criarAlocacao(Usuario $usuario, string $nome): Alocacao
    {
        return Alocacao::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => $nome,
            'saldo_consolidado' => 0,
        ]);
    }

    private function registrarSeparacao(Usuario $usuario, Alocacao $alocacao, ContaBancaria $conta, float $valor): MovimentacaoSaldoGuardado
    {
        $movimentacao = MovimentacaoSaldoGuardado::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'id_conta_bancaria_origem' => $conta->id_conta_bancaria,
            'id_alocacao' => $alocacao->id_alocacao,
            'tipo' => TipoMovimentacaoSaldoGuardado::Separacao,
            'valor' => $valor,
            'data_movimentacao' => now()->toDateString(),
        ]);

        app(ConsolidadorSaldoAlocacao::class)->recalcularSaldoConsolidadoDaAlocacao((int) $alocacao->id_alocacao);

        return $movimentacao;
    }

    private function criarItemPlanoComAlocacao(Usuario $usuario, Alocacao $alocacao, ContaBancaria $conta): ItemPlanoFinanceiro
    {
        $plano = PlanoFinanceiro::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Essencial',
            'situacao' => SituacaoPlanoFinanceiro::Ativo,
            'padrao' => SimNao::Sim,
        ]);

        return ItemPlanoFinanceiro::query()->create([
            'id_plano_financeiro' => $plano->id_plano_financeiro,
            'id_usuario' => $usuario->id_usuario,
            'id_alocacao' => $alocacao->id_alocacao,
            'nome' => $alocacao->nome,
            'cor' => CorItemPlanoFinanceiro::padrao(),
            'tipo_calculo' => TipoCalculoItemPlano::Percentual,
            'valor' => 10,
            'separar_do_saldo' => SimNao::Sim,
            'id_conta_bancaria_destino' => $conta->id_conta_bancaria,
            'ordem' => 0,
        ]);
    }
}
