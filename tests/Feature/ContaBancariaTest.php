<?php

namespace Tests\Feature;

use App\Enum\AcabamentoVisual;
use App\Enum\FormaPagamento;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoContaBancaria;
use App\Enum\TipoLancamento;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContaBancariaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_criar_conta_com_acabamento(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->post('/contas-bancarias', $this->dadosContaValida([
                'acabamento' => AcabamentoVisual::Roxo->value,
            ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/contas-bancarias');

        $this->assertDatabaseHas('contas_bancarias', [
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Nubank',
            'acabamento' => AcabamentoVisual::Roxo->value,
        ]);
    }

    public function test_acabamento_vazio_assume_o_padrao(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->post('/contas-bancarias', $this->dadosContaValida([
                'acabamento' => '',
            ]));

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contas_bancarias', [
            'id_usuario' => $usuario->id_usuario,
            'acabamento' => AcabamentoVisual::padrao()->value,
        ]);
    }

    public function test_acabamento_invalido_e_rejeitado(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->post('/contas-bancarias', $this->dadosContaValida([
                'acabamento' => 'dourado',
            ]));

        $response->assertSessionHasErrors('acabamento');
        $this->assertDatabaseCount('contas_bancarias', 0);
    }

    public function test_definir_conta_padrao_desconto_desmarca_a_padrao_anterior(): void
    {
        $usuario = Usuario::factory()->create();
        $contaPadraoAtual = $this->criarContaBancaria($usuario, ['nome' => 'Inter', 'padrao_desconto' => SimNao::Sim]);
        $novaContaPadrao = $this->criarContaBancaria($usuario, ['nome' => 'Nubank', 'padrao_desconto' => SimNao::Nao]);

        $response = $this
            ->actingAs($usuario)
            ->patch('/contas-bancarias/'.$novaContaPadrao->getRouteKey().'/padrao-desconto');

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/contas-bancarias');

        $this->assertSame(SimNao::Sim, $novaContaPadrao->refresh()->padrao_desconto);
        $this->assertSame(SimNao::Nao, $contaPadraoAtual->refresh()->padrao_desconto);
    }

    public function test_usuario_nao_pode_definir_padrao_desconto_em_conta_de_outro_usuario(): void
    {
        $dono = Usuario::factory()->create();
        $outroUsuario = Usuario::factory()->create();
        $conta = $this->criarContaBancaria($dono, ['padrao_desconto' => SimNao::Nao]);

        $response = $this
            ->actingAs($outroUsuario)
            ->patch('/contas-bancarias/'.$conta->getRouteKey().'/padrao-desconto');

        $response->assertForbidden();
        $this->assertSame(SimNao::Nao, $conta->refresh()->padrao_desconto);
    }

    public function test_usuario_pode_arquivar_e_desarquivar_conta(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarContaBancaria($usuario);

        $this
            ->actingAs($usuario)
            ->patch('/contas-bancarias/'.$conta->getRouteKey().'/arquivar', ['arquivada' => 'S'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/contas-bancarias');

        $this->assertSame(SimNao::Sim, $conta->refresh()->arquivada);

        $this
            ->actingAs($usuario)
            ->patch('/contas-bancarias/'.$conta->getRouteKey().'/arquivar', ['arquivada' => 'N'])
            ->assertSessionHasNoErrors();

        $this->assertSame(SimNao::Nao, $conta->refresh()->arquivada);
    }

    public function test_usuario_nao_pode_arquivar_conta_de_outro_usuario(): void
    {
        $dono = Usuario::factory()->create();
        $outroUsuario = Usuario::factory()->create();
        $conta = $this->criarContaBancaria($dono);

        $response = $this
            ->actingAs($outroUsuario)
            ->patch('/contas-bancarias/'.$conta->getRouteKey().'/arquivar', ['arquivada' => 'S']);

        $response->assertForbidden();
        $this->assertSame(SimNao::Nao, $conta->refresh()->arquivada);
    }

    public function test_index_envia_movimentacoes_recentes_somente_das_contas_ativas_sem_canceladas(): void
    {
        $usuario = Usuario::factory()->create();
        $contaAtiva = $this->criarContaBancaria($usuario, ['nome' => 'Ativa']);
        $contaArquivada = $this->criarContaBancaria($usuario, ['nome' => 'Arquivada', 'arquivada' => SimNao::Sim]);

        $this->criarLancamentoNaConta($usuario, $contaAtiva, 'Salário', TipoLancamento::Receita, SituacaoLancamento::Recebido);
        $this->criarLancamentoNaConta($usuario, $contaAtiva, 'Despesa cancelada', TipoLancamento::Despesa, SituacaoLancamento::Cancelado);
        $this->criarLancamentoNaConta($usuario, $contaArquivada, 'Despesa antiga', TipoLancamento::Despesa, SituacaoLancamento::Pago);

        $response = $this
            ->actingAs($usuario)
            ->get('/contas-bancarias');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('ContaBancaria/Index')
            ->has('contasBancarias', 2)
            ->has('acabamentos', count(AcabamentoVisual::cases()))
            ->has("movimentacoesRecentesPorConta.{$contaAtiva->id_conta_bancaria}", 1)
            ->where("movimentacoesRecentesPorConta.{$contaAtiva->id_conta_bancaria}.0.descricao", 'Salário')
            ->where("movimentacoesRecentesPorConta.{$contaAtiva->id_conta_bancaria}.0.tipo", TipoLancamento::Receita->value)
            ->missing("movimentacoesRecentesPorConta.{$contaArquivada->id_conta_bancaria}")
        );
    }

    private function dadosContaValida(array $sobrescrever = []): array
    {
        return [
            'nome' => 'Nubank',
            'saldo_inicial' => '1.500,00',
            'tipo' => TipoContaBancaria::Corrente->value,
            'acabamento' => AcabamentoVisual::Esmeralda->value,
            'padrao_desconto' => 'N',
            'exibir_resumo' => 'S',
            ...$sobrescrever,
        ];
    }

    private function criarContaBancaria(Usuario $usuario, array $sobrescrever = []): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Conta teste',
            'saldo_inicial' => 1000,
            'tipo' => TipoContaBancaria::Corrente,
            'acabamento' => AcabamentoVisual::Esmeralda,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Nao,
            'exibir_resumo' => SimNao::Sim,
            ...$sobrescrever,
        ]);
    }

    private function criarLancamentoNaConta(
        Usuario $usuario,
        ContaBancaria $conta,
        string $descricao,
        TipoLancamento $tipo,
        SituacaoLancamento $situacao,
    ): Lancamento {
        return Lancamento::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => $descricao,
            'valor' => 150,
            'data_vencimento' => now()->toDateString(),
            'tipo' => $tipo,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'situacao' => $situacao,
            'eh_recorrencia' => SimNao::Nao,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
        ]);
    }
}
