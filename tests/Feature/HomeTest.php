<?php

namespace Tests\Feature;

use App\Enum\AcabamentoVisual;
use App\Enum\BandeiraCartaoCredito;
use App\Enum\FormaPagamento;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoContaBancaria;
use App\Enum\TipoLancamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Objetivo\Objetivo;
use App\Models\Usuario\Usuario;
use App\Services\Home\HomeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private ContaBancaria $conta;

    private Carbon $hoje;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::factory()->create();
        $this->hoje = Carbon::create(2026, 9, 10);
        $this->conta = $this->criarContaBancaria('Banco Inter', 2000, SimNao::Sim);
    }

    public function test_compromissos_incluem_atrasados_e_proximos_sete_dias(): void
    {
        $this->criarDespesaNaConta('Conta atrasada', '2026-09-05', 100);
        $this->criarDespesaNaConta('Mercado', '2026-09-12', 200);
        $this->criarDespesaNaConta('Limite da janela', '2026-09-17', 50);
        $this->criarDespesaNaConta('Fora da janela', '2026-09-18', 999);
        $this->criarDespesaNaConta('Já paga', '2026-09-11', 300, SituacaoLancamento::Pago);

        $resumo = $this->obterResumoDoDia();

        $this->assertSame(
            ['Conta atrasada', 'Mercado', 'Limite da janela'],
            array_column($resumo['compromissos'], 'descricao'),
        );
        $this->assertTrue($resumo['compromissos'][0]['atrasado']);
        $this->assertFalse($resumo['compromissos'][1]['atrasado']);
        $this->assertSame(3, $resumo['a_pagar']['quantidade']);
        $this->assertSame('350,00', $resumo['a_pagar']['total']);
        $this->assertSame('Conta atrasada, Mercado e mais 1', $resumo['a_pagar']['resumo_descricoes']);
    }

    public function test_despesas_de_cartao_nao_entram_nos_compromissos(): void
    {
        $cartao = $this->criarCartaoCredito('Nubank', SimNao::Nao);

        Lancamento::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'descricao' => 'Compra no cartão',
            'valor' => 150,
            'data_vencimento' => '2026-09-12',
            'tipo' => TipoLancamento::Despesa,
            'forma_pagamento' => FormaPagamento::CartaoCredito,
            'id_cartao_credito' => $cartao->id_cartao_credito,
            'situacao' => SituacaoLancamento::Pendente,
            'eh_recorrencia' => SimNao::Nao,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
        ]);

        $resumo = $this->obterResumoDoDia();

        $this->assertSame([], $resumo['compromissos']);
        $this->assertSame(0, $resumo['a_pagar']['quantidade']);
    }

    public function test_saldo_livre_usa_saldo_disponivel_e_conta_principal(): void
    {
        $resumo = $this->obterResumoDoDia();

        $this->assertSame('2.000,00', $resumo['saldo_livre']['valor']);
        $this->assertSame('Banco Inter', $resumo['saldo_livre']['conta_principal_nome']);
        $this->assertSame(TipoContaBancaria::Corrente->rotulo(), $resumo['saldo_livre']['conta_principal_tipo_rotulo']);
    }

    public function test_cartao_preferencial_prioriza_o_cartao_padrao(): void
    {
        $this->criarCartaoCredito('Azul', SimNao::Nao);
        $this->criarCartaoCredito('Zeta Padrão', SimNao::Sim);

        $resumo = $this->obterResumoDoDia();

        $this->assertSame('Zeta Padrão', $resumo['cartao_preferencial']['nome']);
        $this->assertSame('5.000,00', $resumo['cartao_preferencial']['limite_disponivel']);
    }

    public function test_meta_em_destaque_e_o_primeiro_objetivo_do_dashboard(): void
    {
        Objetivo::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'descricao' => 'Carro novo',
            'valor_meta' => 10000,
            'data_limite' => '2027-04-30',
            'exibir_dashboard' => SimNao::Sim,
        ]);

        $resumo = $this->obterResumoDoDia();

        $this->assertSame('Carro novo', $resumo['meta_em_destaque']['descricao']);
        $this->assertSame('30/04/2027', $resumo['meta_em_destaque']['data_limite_formatada']);
        $this->assertSame('10.000,00', $resumo['meta_em_destaque']['valor_meta']);
    }

    public function test_pagina_home_renderiza_resumo_do_dia(): void
    {
        $this->actingAs($this->usuario)
            ->get('/home')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Home')
                ->has('resumoDoDia.saldo_livre')
                ->has('resumoDoDia.compromissos')
                ->has('resumoDoDia.dica_financeira.partes')
            );
    }

    private function obterResumoDoDia(): array
    {
        return app(HomeService::class)->obterResumoDoDia((int) $this->usuario->id_usuario, $this->hoje);
    }

    private function criarContaBancaria(string $nome, float $saldoInicial, SimNao $principal): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'nome' => $nome,
            'saldo_inicial' => $saldoInicial,
            'tipo' => TipoContaBancaria::Corrente,
            'acabamento' => AcabamentoVisual::Esmeralda,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => $principal,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }

    private function criarCartaoCredito(string $nome, SimNao $padrao): CartaoCredito
    {
        return CartaoCredito::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'nome' => $nome,
            'nome_titular' => 'Titular teste',
            'limite_total' => 5000,
            'dia_fechamento' => 1,
            'dia_vencimento' => 10,
            'bandeira' => BandeiraCartaoCredito::Visa,
            'acabamento' => AcabamentoVisual::Esmeralda,
            'padrao' => $padrao,
            'arquivada' => SimNao::Nao,
        ]);
    }

    private function criarDespesaNaConta(
        string $descricao,
        string $dataVencimento,
        float $valor,
        SituacaoLancamento $situacao = SituacaoLancamento::Pendente,
    ): Lancamento {
        return Lancamento::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'descricao' => $descricao,
            'valor' => $valor,
            'data_vencimento' => $dataVencimento,
            'tipo' => TipoLancamento::Despesa,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $this->conta->id_conta_bancaria,
            'situacao' => $situacao,
            'eh_recorrencia' => SimNao::Nao,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
        ]);
    }
}
