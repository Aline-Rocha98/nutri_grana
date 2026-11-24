<?php

namespace Tests\Feature\EspacoCompartilhado;

use App\Enum\AcabamentoVisual;
use App\Enum\BandeiraCartaoCredito;
use App\Enum\FormaPagamento;
use App\Enum\OrigemLancamentoFinanceiro;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\StatusLancamentoCompartilhado;
use App\Enum\TipoContaBancaria;
use App\Enum\TipoLancamento;
use App\Enum\TipoOrcamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\EspacoCompartilhado\MembroEspacoCompartilhado;
use App\Models\Lancamento\Lancamento;
use App\Models\Orcamento\Orcamento;
use App\Models\Usuario\Usuario;
use App\Repositories\CartaoCredito\CartaoCreditoRepository;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PreparaEspacoCompartilhado;
use Tests\TestCase;

class ConsultaFinanceiraUnificadaTest extends TestCase
{
    use PreparaEspacoCompartilhado;
    use RefreshDatabase;

    private Usuario $carolina;

    private Usuario $aline;

    private EspacoCompartilhado $espaco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 11, 13));
        $this->carolina = $this->criarUsuarioComCompartilhamento('Carolina');
        $this->aline = $this->criarUsuarioComCompartilhamento('Aline');
        $this->espaco = $this->criarEspacoComMembros($this->carolina, $this->aline);
    }

    public function test_responsavel_ve_compartilhado_na_listagem_e_totais_pessoais(): void
    {
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);
        $this->criarLancamentoPessoal($this->carolina, 'Mercado', 100);
        $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'descricao' => 'Aluguel',
            'valor' => '1500.00',
            'data_vencimento' => '2026-11-20',
            'id_categoria' => $categoria->id_categoria,
        ]);

        $this->autenticarComo($this->carolina)
            ->get('/lancamentos/2026/11')
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->component('Lancamento/Index')
                ->has('lancamentos.data', 2)
                ->where('totais.despesas', 1600)
                ->where('totais.pendentes', 1600)
                ->where('contadoresPorTipo.despesas', 2)
                ->where('lancamentos.data', fn ($itens) => collect($itens)->pluck('origem')->sort()->values()->all() === [
                    OrigemLancamentoFinanceiro::Compartilhado->value,
                    OrigemLancamentoFinanceiro::Pessoal->value,
                ])
            );

        $this->assertDatabaseCount('lancamentos', 1);
    }

    public function test_nao_responsavel_nao_ve_compartilhado_na_lista_pessoal(): void
    {
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);
        $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'descricao' => 'Aluguel',
            'valor' => '1500.00',
            'data_vencimento' => '2026-11-20',
            'id_categoria' => $categoria->id_categoria,
        ]);

        $this->autenticarComo($this->aline)
            ->get('/lancamentos/2026/11')
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->has('lancamentos.data', 0)
                ->where('totais.despesas', 0)
            );
    }

    public function test_pago_e_pendente_compartilhados_entram_sem_duplicata(): void
    {
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);
        $conta = $this->criarContaBancariaPadrao($this->carolina);

        $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'descricao' => 'Internet',
            'valor' => '100.00',
            'data_vencimento' => '2026-11-10',
            'id_categoria' => $categoria->id_categoria,
        ]);

        LancamentoCompartilhado::factory()
            ->doEspaco($this->espaco)
            ->sobResponsabilidadeDe($this->carolina)
            ->comCategoria($categoria->id_categoria)
            ->pago()
            ->create([
                'descricao' => 'Energia',
                'valor' => '200.00',
                'data_vencimento' => '2026-11-05',
                'forma_pagamento' => FormaPagamento::ContaBancaria,
                'id_conta_bancaria' => $conta->id_conta_bancaria,
            ]);

        $this->autenticarComo($this->carolina)
            ->get('/lancamentos/2026/11')
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->has('lancamentos.data', 2)
                ->where('totais.despesas', 300)
                ->where('totais.despesas_pagas', 200)
                ->where('totais.pendentes', 100)
            );

        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_orcamento_considera_compartilhado_do_responsavel(): void
    {
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina, 'Moradia');
        Orcamento::query()->create([
            'id_usuario' => $this->carolina->id_usuario,
            'tipo' => TipoOrcamento::PorCategoria,
            'id_categoria' => $categoria->id_categoria,
            'valor_mensal' => 2000,
            'exibir_dashboard' => SimNao::Sim,
        ]);

        $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'descricao' => 'Aluguel',
            'valor' => '1500.00',
            'data_vencimento' => '2026-11-20',
            'id_categoria' => $categoria->id_categoria,
        ]);

        $this->autenticarComo($this->carolina)
            ->get('/orcamentos/2026/11')
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->where('orcamentos.0.gasto_mes_numero', 1500)
                ->where('orcamentos.0.percentual', 75)
            );
    }

    public function test_endpoint_categorias_do_membro_retorna_saidas_do_responsavel(): void
    {
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina, 'Moradia');
        $membro = MembroEspacoCompartilhado::query()
            ->where('id_espaco_compartilhado', $this->espaco->id_espaco_compartilhado)
            ->where('id_usuario', $this->carolina->id_usuario)
            ->firstOrFail();

        $this->autenticarComo($this->aline)
            ->get(route('compartilhados.membros.categorias', [
                'espacoCompartilhado' => $this->espaco,
                'membroEspaco' => $membro,
            ]))
            ->assertOk()
            ->assertJsonPath('categorias.0.id', $categoria->id_categoria)
            ->assertJsonPath('categorias.0.nome', 'Moradia');
    }

    public function test_saldo_da_conta_considera_compartilhado_pago(): void
    {
        $conta = ContaBancaria::query()->create([
            'id_usuario' => $this->carolina->id_usuario,
            'nome' => 'Inter',
            'saldo_inicial' => 1000,
            'tipo' => TipoContaBancaria::Corrente,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Sim,
            'exibir_resumo' => SimNao::Sim,
        ]);
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);

        LancamentoCompartilhado::factory()
            ->doEspaco($this->espaco)
            ->sobResponsabilidadeDe($this->carolina)
            ->comCategoria($categoria->id_categoria)
            ->pago()
            ->create([
                'valor' => '250.00',
                'forma_pagamento' => FormaPagamento::ContaBancaria,
                'id_conta_bancaria' => $conta->id_conta_bancaria,
            ]);

        $contas = app(ContaBancariaRepository::class)->listarPorUsuario((int) $this->carolina->id_usuario);
        $contaComSaldo = $contas->firstWhere('id_conta_bancaria', $conta->id_conta_bancaria);

        $this->assertSame(750.0, (float) $contaComSaldo->saldo_atual);
    }

    public function test_uso_do_cartao_considera_compartilhado_pago(): void
    {
        $cartao = CartaoCredito::query()->create([
            'id_usuario' => $this->carolina->id_usuario,
            'nome' => 'Nubank',
            'nome_titular' => 'Carolina',
            'limite_total' => 5000,
            'dia_fechamento' => 1,
            'dia_vencimento' => 10,
            'bandeira' => BandeiraCartaoCredito::Visa,
            'acabamento' => AcabamentoVisual::Esmeralda,
            'padrao' => SimNao::Sim,
            'arquivada' => SimNao::Nao,
        ]);
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);

        LancamentoCompartilhado::factory()
            ->doEspaco($this->espaco)
            ->sobResponsabilidadeDe($this->carolina)
            ->comCategoria($categoria->id_categoria)
            ->pago()
            ->create([
                'valor' => '320.00',
                'forma_pagamento' => FormaPagamento::CartaoCredito,
                'id_cartao_credito' => $cartao->id_cartao_credito,
            ]);

        $uso = app(CartaoCreditoRepository::class)->somarUsoEmAberto((int) $cartao->id_cartao_credito);

        $this->assertSame(320.0, $uso);
    }

    public function test_com_modulo_desabilitado_nao_inclui_compartilhado_na_lista_nem_totais(): void
    {
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);
        $this->criarLancamentoPessoal($this->carolina, 'Mercado', 100);
        $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'descricao' => 'Aluguel',
            'valor' => '1500.00',
            'data_vencimento' => '2026-11-20',
            'id_categoria' => $categoria->id_categoria,
        ]);

        $this->carolina->forceFill(['compartilhamento_habilitado' => SimNao::Nao])->save();

        $this->autenticarComo($this->carolina)
            ->get('/lancamentos/2026/11')
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->has('lancamentos.data', 1)
                ->where('lancamentos.data.0.descricao', 'Mercado')
                ->where('totais.despesas', 100)
                ->where('totais.pendentes', 100)
                ->where('contadoresPorTipo.despesas', 1)
            );
    }

    public function test_com_modulo_desabilitado_saldo_ignora_compartilhado_pago(): void
    {
        $conta = ContaBancaria::query()->create([
            'id_usuario' => $this->carolina->id_usuario,
            'nome' => 'Inter',
            'saldo_inicial' => 1000,
            'tipo' => TipoContaBancaria::Corrente,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Sim,
            'exibir_resumo' => SimNao::Sim,
        ]);
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);

        LancamentoCompartilhado::factory()
            ->doEspaco($this->espaco)
            ->sobResponsabilidadeDe($this->carolina)
            ->comCategoria($categoria->id_categoria)
            ->pago()
            ->create([
                'valor' => '250.00',
                'forma_pagamento' => FormaPagamento::ContaBancaria,
                'id_conta_bancaria' => $conta->id_conta_bancaria,
            ]);

        $this->carolina->forceFill(['compartilhamento_habilitado' => SimNao::Nao])->save();

        $contas = app(ContaBancariaRepository::class)->listarPorUsuario((int) $this->carolina->id_usuario);
        $contaComSaldo = $contas->firstWhere('id_conta_bancaria', $conta->id_conta_bancaria);

        $this->assertSame(1000.0, (float) $contaComSaldo->saldo_atual);
    }

    public function test_home_a_pagar_inclui_pendente_compartilhado_do_responsavel(): void
    {
        $this->criarContaBancariaPadrao($this->carolina);
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);
        $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'descricao' => 'Condominio',
            'valor' => '400.00',
            'data_vencimento' => '2026-11-15',
            'id_categoria' => $categoria->id_categoria,
        ]);

        $resumo = app(\App\Services\Home\HomeService::class)
            ->obterResumoDoDia((int) $this->carolina->id_usuario, Carbon::create(2026, 11, 13));

        $this->assertSame(1, $resumo['a_pagar']['quantidade']);
        $this->assertSame('400,00', $resumo['a_pagar']['total']);
        $this->assertSame(OrigemLancamentoFinanceiro::Compartilhado->value, collect($resumo['compromissos'])->first()['origem'] ?? null);
        $this->assertSame('Condominio', $resumo['compromissos'][0]['descricao']);
    }

    public function test_home_com_modulo_desabilitado_nao_inclui_compartilhado_em_a_pagar(): void
    {
        $this->criarContaBancariaPadrao($this->carolina);
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);
        $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'descricao' => 'Condominio',
            'valor' => '400.00',
            'data_vencimento' => '2026-11-15',
            'id_categoria' => $categoria->id_categoria,
        ]);

        $this->carolina->forceFill(['compartilhamento_habilitado' => SimNao::Nao])->save();

        $resumo = app(\App\Services\Home\HomeService::class)
            ->obterResumoDoDia((int) $this->carolina->id_usuario, Carbon::create(2026, 11, 13));

        $this->assertSame(0, $resumo['a_pagar']['quantidade']);
        $this->assertSame([], $resumo['compromissos']);
    }

    private function criarLancamentoPessoal(Usuario $usuario, string $descricao, float $valor): Lancamento
    {
        $conta = ContaBancaria::query()
            ->where('id_usuario', $usuario->id_usuario)
            ->first() ?? $this->criarContaBancariaPadrao($usuario);

        return Lancamento::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => $descricao,
            'valor' => $valor,
            'data_vencimento' => '2026-11-15',
            'tipo' => TipoLancamento::Despesa,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'situacao' => SituacaoLancamento::Pendente,
            'eh_recorrencia' => SimNao::Nao,
        ]);
    }
}
