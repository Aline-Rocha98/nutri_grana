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
use App\Models\Orcamento\Orcamento;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrcamentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_criar_orcamento_por_categoria(): void
    {
        $usuario = Usuario::factory()->create();
        $categoria = $this->criarCategoriaPrincipal($usuario, 'Alimentação');

        $response = $this
            ->actingAs($usuario)
            ->post('/orcamentos', [
                'tipo' => TipoOrcamento::PorCategoria->value,
                'id_categoria' => $categoria->id_categoria,
                'valor_mensal' => '1.000,00',
                'exibir_dashboard' => 'S',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/orcamentos/'.now()->year.'/'.now()->month);

        $this->assertDatabaseHas('orcamentos', [
            'id_usuario' => $usuario->id_usuario,
            'tipo' => TipoOrcamento::PorCategoria->value,
            'id_categoria' => $categoria->id_categoria,
            'valor_mensal' => 1000.00,
            'exibir_dashboard' => 'S',
        ]);
    }

    public function test_nao_permite_dois_orcamentos_para_mesma_categoria(): void
    {
        $usuario = Usuario::factory()->create();
        $categoria = $this->criarCategoriaPrincipal($usuario, 'Alimentação');
        $this->criarOrcamento($usuario, $categoria, 1000);

        $response = $this
            ->actingAs($usuario)
            ->from('/orcamentos')
            ->post('/orcamentos', [
                'tipo' => TipoOrcamento::PorCategoria->value,
                'id_categoria' => $categoria->id_categoria,
                'valor_mensal' => '500,00',
                'exibir_dashboard' => 'N',
            ]);

        $response->assertSessionHasErrors('id_categoria');
        $this->assertDatabaseCount('orcamentos', 1);
    }

    public function test_listagem_mostra_progresso_do_mes(): void
    {
        $usuario = Usuario::factory()->create();
        $categoria = $this->criarCategoriaPrincipal($usuario, 'Alimentação');
        $sub = $this->criarSubcategoria($usuario, $categoria, 'Mercado');
        $conta = $this->criarConta($usuario);
        $this->criarOrcamento($usuario, $categoria, 1000);

        $this->criarDespesa($usuario, $conta, $sub, 620);

        $response = $this
            ->actingAs($usuario)
            ->get('/orcamentos');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Orcamento/Index')
            ->where('ano', (int) now()->year)
            ->where('mes', (int) now()->month)
            ->has('orcamentos', 1)
            ->where('orcamentos.0.gasto_mes_numero', 620)
            ->where('orcamentos.0.valor_mensal_numero', 1000)
            ->where('orcamentos.0.percentual', 62)
            ->where('orcamentos.0.ultrapassado', false)
        );
    }

    public function test_listagem_respeita_mes_selecionado_na_rota(): void
    {
        $usuario = Usuario::factory()->create();
        $categoria = $this->criarCategoriaPrincipal($usuario, 'Alimentação');
        $conta = $this->criarConta($usuario);
        $this->criarOrcamento($usuario, $categoria, 1000);

        $mesAnterior = now()->subMonthNoOverflow();
        $this->criarDespesa(
            $usuario,
            $conta,
            $categoria,
            400,
            $mesAnterior->toDateString(),
        );

        $response = $this
            ->actingAs($usuario)
            ->get('/orcamentos/'.$mesAnterior->year.'/'.$mesAnterior->month);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Orcamento/Index')
            ->where('ano', (int) $mesAnterior->year)
            ->where('mes', (int) $mesAnterior->month)
            ->where('orcamentos.0.gasto_mes_numero', 400)
            ->where('orcamentos.0.percentual', 40)
        );
    }

    public function test_gasto_de_outro_mes_nao_entra_no_progresso(): void
    {
        $usuario = Usuario::factory()->create();
        $categoria = $this->criarCategoriaPrincipal($usuario, 'Alimentação');
        $conta = $this->criarConta($usuario);
        $this->criarOrcamento($usuario, $categoria, 1000);

        $this->criarDespesa(
            $usuario,
            $conta,
            $categoria,
            800,
            now()->subMonthNoOverflow()->toDateString(),
        );

        $response = $this
            ->actingAs($usuario)
            ->get('/orcamentos');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Orcamento/Index')
            ->where('orcamentos.0.gasto_mes_numero', 0)
            ->where('orcamentos.0.ultrapassado', false)
        );
    }

    public function test_lancamento_que_ultrapassa_orcamento_exige_confirmacao(): void
    {
        $usuario = Usuario::factory()->create();
        $categoria = $this->criarCategoriaPrincipal($usuario, 'Alimentação');
        $conta = $this->criarConta($usuario);
        $this->criarOrcamento($usuario, $categoria, 1000);
        $this->criarDespesa($usuario, $conta, $categoria, 800);

        $response = $this
            ->actingAs($usuario)
            ->from('/lancamentos')
            ->post('/lancamentos', [
                'descricao' => 'Jantar',
                'valor' => '300,00',
                'data_vencimento' => now()->toDateString(),
                'tipo' => TipoLancamento::Despesa->value,
                'forma_pagamento' => FormaPagamento::ContaBancaria->value,
                'id_conta_bancaria' => $conta->id_conta_bancaria,
                'situacao' => SituacaoLancamento::Pendente->value,
                'id_categoria' => $categoria->id_categoria,
            ]);

        $response->assertSessionHasErrors('confirmar_ultrapassagem_orcamento');
        $this->assertDatabaseCount('lancamentos', 1);
    }

    public function test_lancamento_ultrapassado_pode_ser_confirmado(): void
    {
        $usuario = Usuario::factory()->create();
        $categoria = $this->criarCategoriaPrincipal($usuario, 'Alimentação');
        $conta = $this->criarConta($usuario);
        $this->criarOrcamento($usuario, $categoria, 1000);
        $this->criarDespesa($usuario, $conta, $categoria, 800);

        $response = $this
            ->actingAs($usuario)
            ->post('/lancamentos', [
                'descricao' => 'Jantar',
                'valor' => '300,00',
                'data_vencimento' => now()->toDateString(),
                'tipo' => TipoLancamento::Despesa->value,
                'forma_pagamento' => FormaPagamento::ContaBancaria->value,
                'id_conta_bancaria' => $conta->id_conta_bancaria,
                'situacao' => SituacaoLancamento::Pendente->value,
                'id_categoria' => $categoria->id_categoria,
                'confirmar_ultrapassagem_orcamento' => true,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lancamentos', 2);
    }

    public function test_usuario_nao_acessa_orcamento_de_outro(): void
    {
        $dono = Usuario::factory()->create();
        $outro = Usuario::factory()->create();
        $categoria = $this->criarCategoriaPrincipal($dono, 'Alimentação');
        $orcamento = $this->criarOrcamento($dono, $categoria, 1000);

        $this
            ->actingAs($outro)
            ->put('/orcamentos/'.$orcamento->getRouteKey(), [
                'id_categoria' => $categoria->id_categoria,
                'valor_mensal' => '200,00',
                'exibir_dashboard' => 'N',
            ])
            ->assertForbidden();
    }

    private function criarOrcamento(Usuario $usuario, Categoria $categoria, float $valorMensal): Orcamento
    {
        return Orcamento::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'tipo' => TipoOrcamento::PorCategoria,
            'id_categoria' => $categoria->id_categoria,
            'valor_mensal' => $valorMensal,
            'exibir_dashboard' => SimNao::Nao,
        ]);
    }

    private function criarCategoriaPrincipal(Usuario $usuario, string $nome): Categoria
    {
        return Categoria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'id_categoria_pai' => null,
            'nivel' => Categoria::NIVEL_PRINCIPAL,
            'padrao' => SimNao::Nao,
            'nome' => $nome,
            'tipo' => TipoCategoria::Saida,
            'icone' => 'restaurant',
            'cor' => '#1fa67e',
            'arquivada' => SimNao::Nao,
        ]);
    }

    private function criarSubcategoria(Usuario $usuario, Categoria $pai, string $nome): Categoria
    {
        return Categoria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'id_categoria_pai' => $pai->id_categoria,
            'nivel' => Categoria::NIVEL_SUBCATEGORIA,
            'padrao' => SimNao::Nao,
            'nome' => $nome,
            'tipo' => $pai->tipo,
            'icone' => $pai->icone,
            'cor' => $pai->cor,
            'arquivada' => SimNao::Nao,
        ]);
    }

    private function criarConta(Usuario $usuario): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Conta teste',
            'saldo_inicial' => 2000,
            'tipo' => TipoContaBancaria::Corrente,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Sim,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }

    private function criarDespesa(
        Usuario $usuario,
        ContaBancaria $conta,
        Categoria $categoria,
        float $valor,
        ?string $dataVencimento = null,
    ): Lancamento {
        return Lancamento::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Despesa teste',
            'valor' => $valor,
            'data_vencimento' => $dataVencimento ?? now()->toDateString(),
            'tipo' => TipoLancamento::Despesa,
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
