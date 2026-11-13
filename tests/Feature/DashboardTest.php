<?php

namespace Tests\Feature;

use App\Enum\AcabamentoVisual;
use App\Enum\BandeiraCartaoCredito;
use App\Enum\FormaPagamento;
use App\Enum\PeriodoDashboard;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoCategoria;
use App\Enum\TipoContaBancaria;
use App\Enum\TipoLancamento;
use App\Enum\WidgetDashboard;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\Categoria\Categoria;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Usuario\Usuario;
use App\Services\Dashboard\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Carbon $referencia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::factory()->create();
        $this->referencia = Carbon::create(2026, 9, 15);
    }

    public function test_resumo_retorna_indicadores_de_contas_e_cartoes(): void
    {
        $this->criarContaBancaria('Nubank', 1000);
        $cartao = $this->criarCartaoCredito(5000, 10);
        $this->criarLancamento('Compra no cartão', TipoLancamento::Despesa, SituacaoLancamento::Pendente, 1000, [
            'forma_pagamento' => FormaPagamento::CartaoCredito,
            'id_cartao_credito' => $cartao->id_cartao_credito,
        ]);

        $resumo = $this->obterWidget(WidgetDashboard::Resumo);

        $this->assertSame('1.000,00', $resumo['dinheiro_disponivel']);
        $this->assertSame(0.0, $resumo['dinheiro_guardado_numero']);
        $this->assertSame(4000.0, $resumo['limite_livre_cartoes_numero']);
        $this->assertSame('5.000,00', $resumo['limite_total_configurado']);
        $this->assertSame(80.0, $resumo['percentual_livre']);
        $this->assertSame('10', $resumo['proximo_dia_vencimento']);
    }

    public function test_contas_exibem_tipo_e_saldo_disponivel(): void
    {
        $this->criarContaBancaria('Inter', 250);

        $contas = $this->obterWidget(WidgetDashboard::Contas);

        $this->assertCount(1, $contas['itens']);
        $this->assertSame('Inter', $contas['itens'][0]['nome']);
        $this->assertSame(TipoContaBancaria::Corrente->value, $contas['itens'][0]['tipo']);
        $this->assertSame('250,00', $contas['itens'][0]['saldo_disponivel']);
        $this->assertSame('250,00', $contas['total']['saldo_disponivel']);
    }

    public function test_categorias_calculam_percentual_de_participacao_no_total(): void
    {
        $this->criarContaBancaria('Nubank', 0);
        $mercado = $this->criarCategoria('Mercado');
        $pets = $this->criarCategoria('Pets');

        $this->criarLancamento('Supermercado', TipoLancamento::Despesa, SituacaoLancamento::Pago, 300, [
            'id_categoria' => $mercado->id_categoria,
        ]);
        $this->criarLancamento('Ração', TipoLancamento::Despesa, SituacaoLancamento::Pago, 100, [
            'id_categoria' => $pets->id_categoria,
        ]);

        $categorias = $this->obterWidget(WidgetDashboard::Categorias);

        $this->assertSame('400,00', $categorias['total']);
        $this->assertSame('Mercado', $categorias['itens'][0]['nome']);
        $this->assertSame(75.0, $categorias['itens'][0]['percentual_participacao']);
        $this->assertSame(25.0, $categorias['itens'][1]['percentual_participacao']);
    }

    public function test_receitas_despesas_retorna_totais_do_periodo(): void
    {
        $this->criarContaBancaria('Nubank', 0);
        $this->criarLancamento('Salário', TipoLancamento::Receita, SituacaoLancamento::Recebido, 1000);
        $this->criarLancamento('Aluguel', TipoLancamento::Despesa, SituacaoLancamento::Pago, 400);

        $receitasDespesas = $this->obterWidget(WidgetDashboard::ReceitasDespesas);
        $totais = $receitasDespesas['totais'];

        $this->assertCount(1, $receitasDespesas['series']);
        $this->assertSame(1000.0, $totais['receitas_numero']);
        $this->assertSame(400.0, $totais['despesas_numero']);
        $this->assertSame(600.0, $totais['saldo_poupado_numero']);
        $this->assertSame(40.0, $totais['percentual_comprometido']);
        $this->assertSame(60.0, $totais['percentual_guardado']);
    }

    public function test_endpoint_retorna_somente_os_widgets_solicitados(): void
    {
        $this->actingAs($this->usuario)
            ->getJson('/dashboard/dados?widgets[]=contas')
            ->assertOk()
            ->assertJsonStructure(['data' => ['contas' => ['itens', 'total']]])
            ->assertJsonMissingPath('data.resumo');
    }

    private function obterWidget(WidgetDashboard $widget): array
    {
        $dados = app(DashboardService::class)->obterDados(
            (int) $this->usuario->id_usuario,
            [$widget->value],
            PeriodoDashboard::Atual,
            $this->referencia,
        );

        return $dados[$widget->value];
    }

    private function criarContaBancaria(string $nome, float $saldoInicial): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'nome' => $nome,
            'saldo_inicial' => $saldoInicial,
            'tipo' => TipoContaBancaria::Corrente,
            'acabamento' => AcabamentoVisual::Esmeralda,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Nao,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }

    private function criarCartaoCredito(float $limiteTotal, int $diaVencimento): CartaoCredito
    {
        return CartaoCredito::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'nome' => 'Cartão teste',
            'nome_titular' => 'Titular teste',
            'limite_total' => $limiteTotal,
            'dia_fechamento' => 1,
            'dia_vencimento' => $diaVencimento,
            'bandeira' => BandeiraCartaoCredito::Visa,
            'acabamento' => AcabamentoVisual::Esmeralda,
            'padrao' => SimNao::Nao,
            'arquivada' => SimNao::Nao,
        ]);
    }

    private function criarCategoria(string $nome): Categoria
    {
        return Categoria::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'nivel' => 1,
            'padrao' => SimNao::Nao,
            'nome' => $nome,
            'tipo' => TipoCategoria::Saida,
            'icone' => 'category',
            'cor' => '#1fa67e',
            'arquivada' => SimNao::Nao,
        ]);
    }

    private function criarLancamento(
        string $descricao,
        TipoLancamento $tipo,
        SituacaoLancamento $situacao,
        float $valor,
        array $sobrescrever = [],
    ): Lancamento {
        return Lancamento::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'descricao' => $descricao,
            'valor' => $valor,
            'data_vencimento' => $this->referencia->copy()->day(10)->toDateString(),
            'tipo' => $tipo,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'situacao' => $situacao,
            'eh_recorrencia' => SimNao::Nao,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
            ...$sobrescrever,
        ]);
    }
}
