<?php

namespace Tests\Feature;

use App\Enum\AcabamentoVisual;
use App\Enum\FormaPagamento;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoCategoria;
use App\Enum\TipoContaBancaria;
use App\Enum\TipoLancamento;
use App\Models\Categoria\Categoria;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LancamentoListagemTest extends TestCase
{
    use RefreshDatabase;

    private const URL_MES = '/lancamentos/2026/9';

    private Usuario $usuario;

    private ContaBancaria $contaInter;

    private ContaBancaria $contaNubank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::factory()->create();
        $this->contaInter = $this->criarContaBancaria('Banco Inter');
        $this->contaNubank = $this->criarContaBancaria('Nubank');
    }

    public function test_filtro_por_tipo_lista_somente_o_tipo_escolhido(): void
    {
        $this->criarLancamento('Salário', TipoLancamento::Receita, SituacaoLancamento::Pendente);
        $this->criarLancamento('Mercado', TipoLancamento::Despesa, SituacaoLancamento::Pendente);

        $this->actingAs($this->usuario)
            ->get(self::URL_MES.'?tipo=receita')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Lancamento/Index')
                ->has('lancamentos.data', 1)
                ->where('lancamentos.data.0.descricao', 'Salário')
                ->where('filtros.tipo', 'receita')
            );
    }

    public function test_filtro_status_efetivados_lista_pagos_e_recebidos(): void
    {
        $this->criarLancamento('Conta de luz', TipoLancamento::Despesa, SituacaoLancamento::Pago);
        $this->criarLancamento('Freela', TipoLancamento::Receita, SituacaoLancamento::Recebido);
        $this->criarLancamento('Internet', TipoLancamento::Despesa, SituacaoLancamento::Pendente);

        $this->actingAs($this->usuario)
            ->get(self::URL_MES.'?status=efetivados')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('lancamentos.data', 2)
                ->where('lancamentos.data.0.esta_efetivado', true)
                ->where('lancamentos.data.1.esta_efetivado', true)
            );
    }

    public function test_filtro_status_pendentes_lista_pendentes_e_previstos_sem_cancelados(): void
    {
        $this->criarLancamento('Internet', TipoLancamento::Despesa, SituacaoLancamento::Pendente);
        $this->criarLancamento('Bônus', TipoLancamento::Receita, SituacaoLancamento::Previsto);
        $this->criarLancamento('Conta de luz', TipoLancamento::Despesa, SituacaoLancamento::Pago);
        $this->criarLancamento('Assinatura cancelada', TipoLancamento::Despesa, SituacaoLancamento::Cancelado);

        $this->actingAs($this->usuario)
            ->get(self::URL_MES.'?status=pendentes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('lancamentos.data', 2));
    }

    public function test_busca_encontra_por_descricao_categoria_e_conta(): void
    {
        $categoriaPets = Categoria::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'nivel' => 1,
            'padrao' => SimNao::Nao,
            'nome' => 'Pets',
            'tipo' => TipoCategoria::Saida,
            'icone' => 'pets',
            'cor' => '#f97316',
            'arquivada' => SimNao::Nao,
        ]);

        $this->criarLancamento('Supermercado', TipoLancamento::Despesa, SituacaoLancamento::Pendente);
        $this->criarLancamento('Ração', TipoLancamento::Despesa, SituacaoLancamento::Pendente, [
            'id_categoria' => $categoriaPets->id_categoria,
        ]);
        $this->criarLancamento('Cinema', TipoLancamento::Despesa, SituacaoLancamento::Pendente, [
            'id_conta_bancaria' => $this->contaNubank->id_conta_bancaria,
        ]);

        $this->actingAs($this->usuario)
            ->get(self::URL_MES.'?busca=mercado')
            ->assertInertia(fn ($page) => $page
                ->has('lancamentos.data', 1)
                ->where('lancamentos.data.0.descricao', 'Supermercado')
                ->where('filtros.busca', 'mercado')
            );

        $this->actingAs($this->usuario)
            ->get(self::URL_MES.'?busca=pets')
            ->assertInertia(fn ($page) => $page
                ->has('lancamentos.data', 1)
                ->where('lancamentos.data.0.descricao', 'Ração')
            );

        $this->actingAs($this->usuario)
            ->get(self::URL_MES.'?busca=nubank')
            ->assertInertia(fn ($page) => $page
                ->has('lancamentos.data', 1)
                ->where('lancamentos.data.0.descricao', 'Cinema')
            );
    }

    public function test_contadores_por_tipo_ignoram_o_tipo_e_respeitam_status_e_busca(): void
    {
        $this->criarLancamento('Salário mensal', TipoLancamento::Receita, SituacaoLancamento::Pendente);
        $this->criarLancamento('Mensalidade academia', TipoLancamento::Despesa, SituacaoLancamento::Pendente);
        $this->criarLancamento('Mensalidade curso', TipoLancamento::Despesa, SituacaoLancamento::Pago);
        $this->criarLancamento('Mercado', TipoLancamento::Despesa, SituacaoLancamento::Pendente);

        $this->actingAs($this->usuario)
            ->get(self::URL_MES.'?tipo=receita&status=pendentes&busca=mens')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('lancamentos.data', 1)
                ->where('contadoresPorTipo.todos', 2)
                ->where('contadoresPorTipo.receitas', 1)
                ->where('contadoresPorTipo.despesas', 1)
            );
    }

    public function test_totais_trazem_quantidade_de_receitas_e_despesas(): void
    {
        $this->criarLancamento('Salário', TipoLancamento::Receita, SituacaoLancamento::Recebido, ['valor' => 3000]);
        $this->criarLancamento('Freela', TipoLancamento::Receita, SituacaoLancamento::Pendente, ['valor' => 500]);
        $this->criarLancamento('Mercado', TipoLancamento::Despesa, SituacaoLancamento::Pendente, ['valor' => 350]);

        $this->actingAs($this->usuario)
            ->get(self::URL_MES)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('totais.quantidade_receitas', 2)
                ->where('totais.quantidade_despesas', 1)
                ->where('totais.percentual_poupado', 90)
            );
    }

    public function test_status_invalido_e_rejeitado(): void
    {
        $this->actingAs($this->usuario)
            ->get(self::URL_MES.'?status=qualquer')
            ->assertSessionHasErrors('status');
    }

    private function criarContaBancaria(string $nome): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'nome' => $nome,
            'saldo_inicial' => 0,
            'tipo' => TipoContaBancaria::Corrente,
            'acabamento' => AcabamentoVisual::Esmeralda,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Nao,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }

    private function criarLancamento(
        string $descricao,
        TipoLancamento $tipo,
        SituacaoLancamento $situacao,
        array $sobrescrever = [],
    ): Lancamento {
        return Lancamento::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'descricao' => $descricao,
            'valor' => 100,
            'data_vencimento' => '2026-09-10',
            'tipo' => $tipo,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $this->contaInter->id_conta_bancaria,
            'situacao' => $situacao,
            'eh_recorrencia' => SimNao::Nao,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
            ...$sobrescrever,
        ]);
    }
}
