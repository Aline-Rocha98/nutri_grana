<?php

namespace Tests\Feature;

use App\Enum\FormaPagamento;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoCategoria;
use App\Enum\TipoContaBancaria;
use App\Enum\TipoLancamento;
use App\Enum\TipoOrcamento;
use App\Models\Categoria\Categoria;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Orcamento\OrcamentoServico;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrcamentoServicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_criar_orcamento_por_servico(): void
    {
        $usuario = Usuario::factory()->create();
        $this->criarConta($usuario, 5000);

        $response = $this
            ->actingAs($usuario)
            ->post('/orcamentos/servico', [
                'descricao' => 'Reforma da loja',
                'valor' => '8.000,00',
                'data_orcamento' => now()->toDateString(),
                'data_validade' => now()->addMonths(3)->toDateString(),
                'observacao' => 'Inclui mão de obra',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/orcamentos/'.now()->year.'/'.now()->month.'?tipo=por_servico');

        $this->assertDatabaseHas('orcamentos_servico', [
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Reforma da loja',
            'valor' => 8000.00,
            'observacao' => 'Inclui mão de obra',
        ]);
    }

    public function test_listagem_por_servico_traz_projecao_financeira(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 5000);
        $categoria = $this->criarCategoria($usuario, 'Salário', TipoCategoria::Entrada);

        $this->criarLancamento(
            $usuario,
            $conta,
            $categoria,
            TipoLancamento::Receita,
            2000,
            now()->addMonth()->toDateString(),
        );

        OrcamentoServico::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Pintura',
            'valor' => 1500,
            'data_orcamento' => now()->toDateString(),
            'data_validade' => now()->addMonths(2)->toDateString(),
            'observacao' => null,
        ]);

        $response = $this
            ->actingAs($usuario)
            ->get('/orcamentos?tipo=por_servico');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Orcamento/Index')
            ->where('tipoAtivo', TipoOrcamento::PorServico->value)
            ->has('orcamentosServico', 1)
            ->where('orcamentosServico.0.descricao', 'Pintura')
            ->where('orcamentosServico.0.valor_numero', 1500)
            ->where('orcamentosServico.0.saldo_atual_contas_numero', 5000)
            ->where('orcamentosServico.0.pago_integralmente_agora', true)
        );
    }

    public function test_simulacao_nao_cria_registro_nem_lancamento(): void
    {
        $usuario = Usuario::factory()->create();
        $this->criarConta($usuario, 1000);

        $response = $this
            ->actingAs($usuario)
            ->post('/orcamentos/servico/simular', [
                'descricao' => 'Reforma',
                'valor' => '6.000,00',
                'data_orcamento' => now()->toDateString(),
                'data_validade' => now()->addMonths(4)->toDateString(),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('simulacao_orcamento_servico')
            ->assertRedirect();

        $this->assertDatabaseCount('orcamentos_servico', 0);
        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_usuario_nao_acessa_orcamento_servico_de_outro(): void
    {
        $dono = Usuario::factory()->create();
        $outro = Usuario::factory()->create();

        $orcamento = OrcamentoServico::query()->create([
            'id_usuario' => $dono->id_usuario,
            'descricao' => 'Reforma',
            'valor' => 3000,
            'data_orcamento' => now()->toDateString(),
            'data_validade' => now()->addMonth()->toDateString(),
            'observacao' => null,
        ]);

        $this
            ->actingAs($outro)
            ->put('/orcamentos/servico/'.$orcamento->getRouteKey(), [
                'descricao' => 'Hack',
                'valor' => '100,00',
                'data_orcamento' => now()->toDateString(),
                'data_validade' => now()->addMonth()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_validade_nao_pode_ser_anterior_a_data_do_orcamento(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->from('/orcamentos?tipo=por_servico')
            ->post('/orcamentos/servico', [
                'descricao' => 'Reforma',
                'valor' => '1.000,00',
                'data_orcamento' => now()->toDateString(),
                'data_validade' => now()->subDay()->toDateString(),
            ]);

        $response->assertSessionHasErrors('data_validade');
        $this->assertDatabaseCount('orcamentos_servico', 0);
    }

    private function criarConta(Usuario $usuario, float $saldoInicial): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Conta teste',
            'saldo_inicial' => $saldoInicial,
            'tipo' => TipoContaBancaria::Corrente,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Sim,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }

    private function criarCategoria(Usuario $usuario, string $nome, TipoCategoria $tipo): Categoria
    {
        return Categoria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'id_categoria_pai' => null,
            'nivel' => Categoria::NIVEL_PRINCIPAL,
            'padrao' => SimNao::Nao,
            'nome' => $nome,
            'tipo' => $tipo,
            'icone' => 'category',
            'cor' => '#1fa67e',
            'arquivada' => SimNao::Nao,
        ]);
    }

    private function criarLancamento(
        Usuario $usuario,
        ContaBancaria $conta,
        Categoria $categoria,
        TipoLancamento $tipo,
        float $valor,
        string $dataVencimento,
    ): Lancamento {
        return Lancamento::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Lançamento teste',
            'valor' => $valor,
            'data_vencimento' => $dataVencimento,
            'tipo' => $tipo,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'situacao' => SituacaoLancamento::Pendente,
            'id_categoria' => $categoria->id_categoria,
            'eh_recorrencia' => SimNao::Nao,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
        ]);
    }
}
