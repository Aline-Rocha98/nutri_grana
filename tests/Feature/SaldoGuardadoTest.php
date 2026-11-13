<?php

namespace Tests\Feature;

use App\Enum\CorItemPlanoFinanceiro;
use App\Enum\FormaPagamento;
use App\Enum\FrequenciaRecorrencia;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\SituacaoPlanoFinanceiro;
use App\Enum\TipoAporteObjetivo;
use App\Enum\TipoCalculoItemPlano;
use App\Enum\TipoContaBancaria;
use App\Enum\TipoLancamento;
use App\Enum\TipoMovimentacaoSaldoGuardado;
use App\Models\Alocacao\Alocacao;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\ContaBancaria\MovimentacaoSaldoGuardado;
use App\Models\Lancamento\Lancamento;
use App\Models\Objetivo\Objetivo;
use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use App\Models\PlanoFinanceiro\PlanoFinanceiro;
use App\Models\Renda\Renda;
use App\Models\Usuario\Usuario;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Services\ContaBancaria\SaldoGuardadoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaldoGuardadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmar_receita_separa_item_do_plano_para_a_conta(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $renda = $this->criarRenda($usuario, $conta, 4000);
        $itemPlano = $this->aplicarPlanoComItemQueSepara($usuario, $renda, $conta, TipoCalculoItemPlano::Percentual, 10);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $conta, 4000);

        $this->confirmarReceita($usuario, $lancamento, '4.000,00')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('movimentacoes_saldo_guardado', [
            'id_usuario' => $usuario->id_usuario,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'id_conta_bancaria_origem' => $conta->id_conta_bancaria,
            'id_item_plano' => $itemPlano->id_item_plano,
            'id_alocacao' => $itemPlano->id_alocacao,
            'id_lancamento_origem' => $lancamento->id_lancamento,
            'tipo' => TipoMovimentacaoSaldoGuardado::Separacao->value,
            'valor' => 400.00,
        ]);

        $this->assertDatabaseHas('alocacoes', [
            'id_alocacao' => $itemPlano->id_alocacao,
            'saldo_consolidado' => 400.00,
        ]);

        $this->assertDatabaseHas('lancamentos', [
            'id_lancamento' => $lancamento->id_lancamento,
            'situacao' => SituacaoLancamento::Recebido->value,
            'valor' => 4000.00,
        ]);
    }

    public function test_somente_itens_que_separam_do_saldo_geram_movimentacao(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $objetivo = $this->criarObjetivo($usuario);
        $renda = $this->criarRenda($usuario, $conta, 5000);
        $plano = $this->aplicarPlanoNaRenda($usuario, $renda);
        $this->criarItemNoPlano($plano, 'Sobrevivência', TipoCalculoItemPlano::Percentual, 50);
        $itemReserva = $this->criarItemNoPlano($plano, 'Reserva', TipoCalculoItemPlano::Percentual, 30, $conta, $objetivo);
        $this->criarItemNoPlano($plano, 'Livre', TipoCalculoItemPlano::Percentual, 20);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $conta, 5000);

        $this->confirmarReceita($usuario, $lancamento, '5.000,00')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('movimentacoes_saldo_guardado', 1);
        $this->assertDatabaseHas('movimentacoes_saldo_guardado', [
            'id_item_plano' => $itemReserva->id_item_plano,
            'id_lancamento_origem' => $lancamento->id_lancamento,
            'valor' => 1500.00,
        ]);
        $this->assertDatabaseCount('aportes_objetivo', 1);
        $this->assertDatabaseHas('aportes_objetivo', [
            'id_objetivo' => $objetivo->id_objetivo,
            'tipo' => TipoAporteObjetivo::DestinacaoRenda->value,
            'valor' => 1500.00,
        ]);

        $repositorio = app(ContaBancariaRepository::class);
        $this->assertSame(3500.0, $repositorio->saldoDisponivelDaConta($conta->fresh()));
    }

    public function test_plano_somente_orientativo_nao_guarda_nada(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $renda = $this->criarRenda($usuario, $conta, 4000);
        $plano = $this->aplicarPlanoNaRenda($usuario, $renda);
        $this->criarItemNoPlano($plano, 'Sobrevivência', TipoCalculoItemPlano::Percentual, 70);
        $this->criarItemNoPlano($plano, 'Livre', TipoCalculoItemPlano::Percentual, 30);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $conta, 4000);

        $this->confirmarReceita($usuario, $lancamento, '4.000,00')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('movimentacoes_saldo_guardado', 0);
        $this->assertSame(4000.0, app(ContaBancariaRepository::class)->saldoDisponivelDaConta($conta->fresh()));
    }

    public function test_nao_separa_nada_antes_da_confirmacao_da_receita(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $objetivo = $this->criarObjetivo($usuario);
        $renda = $this->criarRenda($usuario, $conta, 4000);
        $this->aplicarPlanoComItemQueSepara($usuario, $renda, $conta, TipoCalculoItemPlano::Percentual, 10, $objetivo);
        $this->criarLancamentoRendaPrevisto($usuario, $renda, $conta, 4000);

        $this->assertDatabaseCount('movimentacoes_saldo_guardado', 0);
        $this->assertDatabaseCount('aportes_objetivo', 0);
    }

    public function test_varios_itens_que_separam_geram_uma_movimentacao_por_item(): void
    {
        $usuario = Usuario::factory()->create();
        $contaSalario = $this->criarConta($usuario, 0, 'Conta salário');
        $contaReserva = $this->criarConta($usuario, 0, 'Conta reserva');
        $renda = $this->criarRenda($usuario, $contaSalario, 4000);
        $plano = $this->aplicarPlanoNaRenda($usuario, $renda);
        $itemReserva = $this->criarItemNoPlano($plano, 'Reserva', TipoCalculoItemPlano::Percentual, 10, $contaReserva);
        $itemInvestimento = $this->criarItemNoPlano($plano, 'Investimentos', TipoCalculoItemPlano::ValorFixo, 300, $contaSalario);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $contaSalario, 4000);

        $this->confirmarReceita($usuario, $lancamento, '4.000,00')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('movimentacoes_saldo_guardado', [
            'id_item_plano' => $itemReserva->id_item_plano,
            'id_conta_bancaria' => $contaReserva->id_conta_bancaria,
            'valor' => 400.00,
        ]);
        $this->assertDatabaseHas('movimentacoes_saldo_guardado', [
            'id_item_plano' => $itemInvestimento->id_item_plano,
            'id_conta_bancaria' => $contaSalario->id_conta_bancaria,
            'valor' => 300.00,
        ]);
    }

    public function test_separacao_usa_valor_efetivamente_recebido(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $renda = $this->criarRenda($usuario, $conta, 4000);
        $this->aplicarPlanoComItemQueSepara($usuario, $renda, $conta, TipoCalculoItemPlano::Percentual, 10);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $conta, 4000);

        $this->confirmarReceita($usuario, $lancamento, '3.500,00')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('movimentacoes_saldo_guardado', [
            'id_lancamento_origem' => $lancamento->id_lancamento,
            'valor' => 350.00,
        ]);
    }

    public function test_saldos_quando_conta_destino_e_a_mesma_da_renda(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 1000);
        $renda = $this->criarRenda($usuario, $conta, 4000);
        $this->aplicarPlanoComItemQueSepara($usuario, $renda, $conta, TipoCalculoItemPlano::Percentual, 10);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $conta, 4000);

        $this->confirmarReceita($usuario, $lancamento, '4.000,00')
            ->assertSessionHasNoErrors();

        $repositorio = app(ContaBancariaRepository::class);
        $contaAtualizada = $conta->fresh();

        $this->assertSame(5000.0, $repositorio->saldoTotalDaConta($contaAtualizada));
        $this->assertSame(400.0, $repositorio->saldoGuardadoDaConta($contaAtualizada));
        $this->assertSame(4600.0, $repositorio->saldoDisponivelDaConta($contaAtualizada));
    }

    public function test_saldos_quando_conta_destino_e_diferente_da_conta_da_renda(): void
    {
        $usuario = Usuario::factory()->create();
        $contaSalario = $this->criarConta($usuario, 0, 'Conta salário');
        $contaReserva = $this->criarConta($usuario, 100, 'Conta reserva');
        $renda = $this->criarRenda($usuario, $contaSalario, 4000);
        $this->aplicarPlanoComItemQueSepara($usuario, $renda, $contaReserva, TipoCalculoItemPlano::ValorFixo, 500);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $contaSalario, 4000);

        $this->confirmarReceita($usuario, $lancamento, '4.000,00')
            ->assertSessionHasNoErrors();

        $repositorio = app(ContaBancariaRepository::class);
        $contaSalarioAtualizada = $contaSalario->fresh();
        $contaReservaAtualizada = $contaReserva->fresh();

        $this->assertSame(3500.0, $repositorio->saldoTotalDaConta($contaSalarioAtualizada));
        $this->assertSame(0.0, $repositorio->saldoGuardadoDaConta($contaSalarioAtualizada));
        $this->assertSame(3500.0, $repositorio->saldoDisponivelDaConta($contaSalarioAtualizada));

        $this->assertSame(600.0, $repositorio->saldoTotalDaConta($contaReservaAtualizada));
        $this->assertSame(500.0, $repositorio->saldoGuardadoDaConta($contaReservaAtualizada));
        $this->assertSame(100.0, $repositorio->saldoDisponivelDaConta($contaReservaAtualizada));
    }

    public function test_item_do_plano_com_objetivo_gera_aporte_automatico(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $renda = $this->criarRenda($usuario, $conta, 4000);
        $objetivo = $this->criarObjetivo($usuario);
        $this->aplicarPlanoComItemQueSepara($usuario, $renda, $conta, TipoCalculoItemPlano::Percentual, 10, $objetivo);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $conta, 4000);

        $this->confirmarReceita($usuario, $lancamento, '4.000,00')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aportes_objetivo', [
            'id_objetivo' => $objetivo->id_objetivo,
            'id_usuario' => $usuario->id_usuario,
            'tipo' => TipoAporteObjetivo::DestinacaoRenda->value,
            'valor' => 400.00,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'id_lancamento' => null,
        ]);

        $separacao = MovimentacaoSaldoGuardado::query()
            ->where('id_lancamento_origem', $lancamento->id_lancamento)
            ->firstOrFail();

        $this->assertNotNull($separacao->id_aporte_objetivo);
    }

    public function test_separacao_nao_e_duplicada_quando_executada_novamente(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $renda = $this->criarRenda($usuario, $conta, 4000);
        $this->aplicarPlanoComItemQueSepara($usuario, $renda, $conta, TipoCalculoItemPlano::Percentual, 10);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $conta, 4000);

        $this->confirmarReceita($usuario, $lancamento, '4.000,00')
            ->assertSessionHasNoErrors();

        app(SaldoGuardadoService::class)->separarItensPlanoDoRecebimentoRenda($lancamento->fresh());

        $this->assertSame(
            1,
            MovimentacaoSaldoGuardado::query()
                ->where('id_lancamento_origem', $lancamento->id_lancamento)
                ->count()
        );
    }

    public function test_excluir_receita_recebida_estorna_separacoes_e_aportes(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $renda = $this->criarRenda($usuario, $conta, 4000);
        $objetivo = $this->criarObjetivo($usuario);
        $this->aplicarPlanoComItemQueSepara($usuario, $renda, $conta, TipoCalculoItemPlano::Percentual, 10, $objetivo);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $conta, 4000);

        $this->confirmarReceita($usuario, $lancamento, '4.000,00')
            ->assertSessionHasNoErrors();

        $this->actingAs($usuario)
            ->delete('/lancamentos/'.$lancamento->getRouteKey())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('movimentacoes_saldo_guardado', 0);
        $this->assertDatabaseMissing('aportes_objetivo', [
            'id_objetivo' => $objetivo->id_objetivo,
            'tipo' => TipoAporteObjetivo::DestinacaoRenda->value,
        ]);
    }

    public function test_estorno_da_receita_recalcula_saldo_consolidado_da_alocacao(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $renda = $this->criarRenda($usuario, $conta, 4000);
        $itemPlano = $this->aplicarPlanoComItemQueSepara($usuario, $renda, $conta, TipoCalculoItemPlano::Percentual, 10);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $conta, 4000);

        $this->confirmarReceita($usuario, $lancamento, '4.000,00')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('alocacoes', [
            'id_alocacao' => $itemPlano->id_alocacao,
            'saldo_consolidado' => 400.00,
        ]);

        $this->actingAs($usuario)
            ->delete('/lancamentos/'.$lancamento->getRouteKey())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('alocacoes', [
            'id_alocacao' => $itemPlano->id_alocacao,
            'saldo_consolidado' => 0,
        ]);
    }

    public function test_rota_antiga_de_resgate_por_conta_nao_existe_mais(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);

        $this->actingAs($usuario)
            ->post('/contas-bancarias/'.$conta->getRouteKey().'/resgatar-saldo-guardado', [
                'valor' => '10,00',
                'data_resgate' => now()->toDateString(),
            ])
            ->assertNotFound();
    }

    public function test_aporte_com_conta_bancaria_respeita_saldo_disponivel(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $renda = $this->criarRenda($usuario, $conta, 1000);
        $this->aplicarPlanoComItemQueSepara($usuario, $renda, $conta, TipoCalculoItemPlano::Percentual, 50);
        $lancamento = $this->criarLancamentoRendaPrevisto($usuario, $renda, $conta, 1000);
        $objetivo = $this->criarObjetivo($usuario);

        $this->confirmarReceita($usuario, $lancamento, '1.000,00')
            ->assertSessionHasNoErrors();

        $this->actingAs($usuario)
            ->from('/objetivos')
            ->post('/objetivos/'.$objetivo->getRouteKey().'/aportes', [
                'tipo' => TipoAporteObjetivo::ContaBancaria->value,
                'valor' => '600,00',
                'data_aporte' => now()->toDateString(),
                'id_conta_bancaria' => $conta->id_conta_bancaria,
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('aportes_objetivo', [
            'id_objetivo' => $objetivo->id_objetivo,
            'tipo' => TipoAporteObjetivo::ContaBancaria->value,
        ]);
    }

    public function test_aporte_manual_nao_aceita_tipo_destinacao_renda(): void
    {
        $usuario = Usuario::factory()->create();
        $objetivo = $this->criarObjetivo($usuario);

        $this->actingAs($usuario)
            ->from('/objetivos')
            ->post('/objetivos/'.$objetivo->getRouteKey().'/aportes', [
                'tipo' => TipoAporteObjetivo::DestinacaoRenda->value,
                'valor' => '100,00',
                'data_aporte' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('tipo');

        $this->assertDatabaseCount('aportes_objetivo', 0);
    }

    private function confirmarReceita(Usuario $usuario, Lancamento $lancamento, string $valorRecebido)
    {
        return $this->actingAs($usuario)
            ->from('/lancamentos')
            ->patch('/lancamentos/'.$lancamento->getRouteKey().'/confirmar-receita', [
                'valor_recebido' => $valorRecebido,
                'data_recebimento' => now()->toDateString(),
            ]);
    }

    private function criarConta(Usuario $usuario, float $saldoInicial = 0, string $nome = 'Conta teste'): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => $nome,
            'saldo_inicial' => $saldoInicial,
            'tipo' => TipoContaBancaria::Corrente,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Nao,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }

    private function criarRenda(Usuario $usuario, ContaBancaria $conta, float $valorEsperado): Renda
    {
        return Renda::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Salário',
            'valor_esperado' => $valorEsperado,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'frequencia' => FrequenciaRecorrencia::Mensal,
            'dia_esperado' => 5,
            'data_inicio' => now()->startOfMonth()->addDays(4)->toDateString(),
        ]);
    }

    private function criarObjetivo(Usuario $usuario): Objetivo
    {
        return Objetivo::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Reserva de emergência',
            'valor_meta' => 10000,
            'data_limite' => now()->addYear()->toDateString(),
            'exibir_dashboard' => SimNao::Nao,
        ]);
    }

    private function aplicarPlanoComItemQueSepara(
        Usuario $usuario,
        Renda $renda,
        ContaBancaria $contaDestino,
        TipoCalculoItemPlano $tipoCalculo,
        float $valor,
        ?Objetivo $objetivo = null,
    ): ItemPlanoFinanceiro {
        $plano = $this->aplicarPlanoNaRenda($usuario, $renda);

        return $this->criarItemNoPlano($plano, 'Reserva', $tipoCalculo, $valor, $contaDestino, $objetivo);
    }

    private function aplicarPlanoNaRenda(Usuario $usuario, Renda $renda): PlanoFinanceiro
    {
        $plano = PlanoFinanceiro::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Essencial',
            'situacao' => SituacaoPlanoFinanceiro::Ativo,
            'padrao' => SimNao::Sim,
        ]);

        $renda->update(['id_plano_financeiro' => $plano->id_plano_financeiro]);

        return $plano;
    }

    private function criarItemNoPlano(
        PlanoFinanceiro $plano,
        string $nome,
        TipoCalculoItemPlano $tipoCalculo,
        float $valor,
        ?ContaBancaria $contaDestino = null,
        ?Objetivo $objetivo = null,
    ): ItemPlanoFinanceiro {
        return ItemPlanoFinanceiro::query()->create([
            'id_plano_financeiro' => $plano->id_plano_financeiro,
            'id_usuario' => $plano->id_usuario,
            'id_alocacao' => $contaDestino ? $this->buscarOuCriarAlocacao((int) $plano->id_usuario, $nome)->id_alocacao : null,
            'nome' => $nome,
            'cor' => CorItemPlanoFinanceiro::padrao(),
            'tipo_calculo' => $tipoCalculo,
            'valor' => $valor,
            'separar_do_saldo' => $contaDestino ? SimNao::Sim : SimNao::Nao,
            'id_conta_bancaria_destino' => $contaDestino?->id_conta_bancaria,
            'id_objetivo' => $contaDestino ? $objetivo?->id_objetivo : null,
            'ordem' => ItemPlanoFinanceiro::query()->where('id_plano_financeiro', $plano->id_plano_financeiro)->count(),
        ]);
    }

    private function buscarOuCriarAlocacao(int $idUsuario, string $nome): Alocacao
    {
        return Alocacao::query()->firstOrCreate(
            ['id_usuario' => $idUsuario, 'nome' => $nome],
            ['saldo_consolidado' => 0],
        );
    }

    private function criarLancamentoRendaPrevisto(
        Usuario $usuario,
        Renda $renda,
        ContaBancaria $conta,
        float $valor,
    ): Lancamento {
        return Lancamento::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => $renda->descricao,
            'valor' => $valor,
            'valor_previsto' => $valor,
            'data_vencimento' => now()->toDateString(),
            'tipo' => TipoLancamento::Receita,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'situacao' => SituacaoLancamento::Previsto,
            'id_renda' => $renda->id_renda,
            'eh_recorrencia' => SimNao::Nao,
        ]);
    }
}
