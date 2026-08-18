<?php

namespace Tests\Feature;

use App\Enum\BandeiraCartaoCredito;
use App\Enum\FormaPagamento;
use App\Enum\ModalidadePagamentoOrcamento;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\StatusOrcamentoServico;
use App\Enum\TipoCategoria;
use App\Enum\TipoContaBancaria;
use App\Enum\TipoLancamento;
use App\Enum\TipoOrcamento;
use App\Models\CartaoCredito\CartaoCredito;
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

    public function test_usuario_pode_criar_cotacao_em_analise_com_pagamento(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 5000);
        $categoria = $this->criarCategoria($usuario, 'Reforma', TipoCategoria::Saida);

        $response = $this
            ->actingAs($usuario)
            ->post('/orcamentos/servico', [
                ...$this->dadosCotacaoValidos($conta),
                'descricao' => 'Reforma da loja',
                'fornecedor' => 'Construtora ABC',
                'valor' => '8.000,00',
                'observacao' => 'Inclui mão de obra',
                'id_categoria' => $categoria->id_categoria,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/orcamentos/'.now()->year.'/'.now()->month.'?tipo=por_servico');

        $this->assertDatabaseHas('orcamentos_servico', [
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Reforma da loja',
            'fornecedor' => 'Construtora ABC',
            'valor' => 8000.00,
            'status' => StatusOrcamentoServico::EmAnalise->value,
            'modalidade_pagamento' => ModalidadePagamentoOrcamento::AVista->value,
            'forma_pagamento' => FormaPagamento::ContaBancaria->value,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
        ]);

        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_listagem_traz_simulacao_com_cenarios(): void
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
            'status' => StatusOrcamentoServico::EmAnalise,
            'modalidade_pagamento' => ModalidadePagamentoOrcamento::Parcelado,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'total_parcelas' => 2,
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
            ->where('orcamentosServico.0.status', StatusOrcamentoServico::EmAnalise->value)
            ->has('orcamentosServico.0.cenarios', 12)
            ->has('orcamentosServico.0.pode_assumir_compromisso')
            ->has('orcamentosServico.0.resumo_compromisso')
            ->has('categorias')
        );
    }

    public function test_aprovar_cotacao_gera_lancamentos_previstos(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 10000);
        $categoriaDespesa = $this->criarCategoria($usuario, 'Serviços', TipoCategoria::Saida);

        $cotacao = OrcamentoServico::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Móveis',
            'valor' => 6000,
            'data_orcamento' => now()->toDateString(),
            'data_validade' => now()->addMonths(6)->toDateString(),
            'status' => StatusOrcamentoServico::EmAnalise,
            'id_categoria' => $categoriaDespesa->id_categoria,
            'modalidade_pagamento' => ModalidadePagamentoOrcamento::Parcelado,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'total_parcelas' => 6,
        ]);

        $response = $this
            ->actingAs($usuario)
            ->post('/orcamentos/servico/'.$cotacao->getRouteKey().'/aprovar', [
                'modalidade_pagamento' => ModalidadePagamentoOrcamento::Parcelado->value,
                'total_parcelas' => 6,
                'forma_pagamento' => FormaPagamento::ContaBancaria->value,
                'id_conta_bancaria' => $conta->id_conta_bancaria,
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orcamentos_servico', [
            'id_orcamento_servico' => $cotacao->id_orcamento_servico,
            'status' => StatusOrcamentoServico::Aprovada->value,
            'total_parcelas' => 6,
        ]);

        $this->assertDatabaseCount('lancamentos', 6);

        $this->assertDatabaseHas('lancamentos', [
            'id_orcamento_servico' => $cotacao->id_orcamento_servico,
            'situacao' => SituacaoLancamento::Previsto->value,
            'tipo' => TipoLancamento::Despesa->value,
        ]);
    }

    public function test_cotacao_em_analise_nao_gera_lancamento_ao_criar(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 1000);

        $this->actingAs($usuario)->post('/orcamentos/servico', [
            ...$this->dadosCotacaoValidos($conta),
            'descricao' => 'Teste',
            'valor' => '1.000,00',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_usuario_nao_acessa_cotacao_de_outro(): void
    {
        $dono = Usuario::factory()->create();
        $outro = Usuario::factory()->create();
        $conta = $this->criarConta($outro, 1000);

        $cotacao = OrcamentoServico::query()->create([
            'id_usuario' => $dono->id_usuario,
            'descricao' => 'Reforma',
            'valor' => 3000,
            'data_orcamento' => now()->toDateString(),
            'data_validade' => now()->addMonth()->toDateString(),
            'status' => StatusOrcamentoServico::EmAnalise,
        ]);

        $this
            ->actingAs($outro)
            ->put('/orcamentos/servico/'.$cotacao->getRouteKey(), [
                ...$this->dadosCotacaoValidos($conta),
                'descricao' => 'Hack',
                'valor' => '100,00',
            ])
            ->assertForbidden();
    }

    public function test_validade_nao_pode_ser_anterior_a_data_da_cotacao(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 1000);

        $response = $this
            ->actingAs($usuario)
            ->from('/orcamentos?tipo=por_servico')
            ->post('/orcamentos/servico', [
                ...$this->dadosCotacaoValidos($conta),
                'descricao' => 'Reforma',
                'valor' => '1.000,00',
                'data_validade' => now()->subDay()->toDateString(),
            ]);

        $response->assertSessionHasErrors('data_validade');
        $this->assertDatabaseCount('orcamentos_servico', 0);
    }

    public function test_listagem_exibe_saldo_da_conta_selecionada(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 5000);
        $categoriaDespesa = $this->criarCategoria($usuario, 'Serviços', TipoCategoria::Saida);

        $this->criarLancamento(
            $usuario,
            $conta,
            $categoriaDespesa,
            TipoLancamento::Despesa,
            10,
            now()->subDay()->toDateString(),
            SituacaoLancamento::Pago,
        );

        OrcamentoServico::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Reforma',
            'valor' => 1500,
            'data_orcamento' => now()->toDateString(),
            'data_validade' => now()->addMonths(2)->toDateString(),
            'status' => StatusOrcamentoServico::EmAnalise,
            'modalidade_pagamento' => ModalidadePagamentoOrcamento::AVista,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'total_parcelas' => 1,
        ]);

        $response = $this
            ->actingAs($usuario)
            ->get('/orcamentos?tipo=por_servico');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('orcamentosServico.0.id_conta_bancaria', $conta->id_conta_bancaria)
            ->where('orcamentosServico.0.saldo_conta_selecionada_numero', 4990)
        );
    }

    public function test_cotacao_sem_id_conta_usa_conta_padrao_na_simulacao(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 3000);

        OrcamentoServico::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Legado sem conta',
            'valor' => 800,
            'data_orcamento' => now()->toDateString(),
            'data_validade' => now()->addMonth()->toDateString(),
            'status' => StatusOrcamentoServico::EmAnalise,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => null,
        ]);

        $response = $this
            ->actingAs($usuario)
            ->get('/orcamentos?tipo=por_servico');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('orcamentosServico.0.id_conta_bancaria', $conta->id_conta_bancaria)
            ->where('orcamentosServico.0.saldo_conta_selecionada_numero', 3000)
            ->where('orcamentosServico.0.conta_bancaria_nome', $conta->nome)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosCotacaoValidos(ContaBancaria $conta): array
    {
        return [
            'data_orcamento' => now()->toDateString(),
            'data_validade' => now()->addMonth()->toDateString(),
            'modalidade_pagamento' => ModalidadePagamentoOrcamento::AVista->value,
            'forma_pagamento' => FormaPagamento::ContaBancaria->value,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
        ];
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

    private function criarCartao(Usuario $usuario, float $limite): CartaoCredito
    {
        return CartaoCredito::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Cartão teste',
            'limite_total' => $limite,
            'dia_fechamento' => 1,
            'dia_vencimento' => 10,
            'bandeira' => BandeiraCartaoCredito::Visa,
            'padrao' => SimNao::Sim,
            'arquivada' => SimNao::Nao,
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
        SituacaoLancamento $situacao = SituacaoLancamento::Pendente,
    ): Lancamento {
        return Lancamento::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Lançamento teste',
            'valor' => $valor,
            'data_vencimento' => $dataVencimento,
            'tipo' => $tipo,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'situacao' => $situacao,
            'id_categoria' => $categoria->id_categoria,
            'eh_recorrencia' => SimNao::Nao,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
        ]);
    }
}
