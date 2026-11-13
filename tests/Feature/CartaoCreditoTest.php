<?php

namespace Tests\Feature;

use App\Enum\AcabamentoVisual;
use App\Enum\BandeiraCartaoCredito;
use App\Enum\FormaPagamento;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\Lancamento\Lancamento;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartaoCreditoTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_criar_cartao_com_titular_digitos_e_acabamento(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->post('/cartoes-credito', $this->dadosCartaoValido([
                'ultimos_digitos' => '8492',
                'acabamento' => AcabamentoVisual::Oceano->value,
            ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/cartoes-credito');

        $this->assertDatabaseHas('cartoes_credito', [
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Nubank Ultravioleta',
            'nome_titular' => 'Aline Rocha',
            'ultimos_digitos' => '8492',
            'acabamento' => AcabamentoVisual::Oceano->value,
        ]);
    }

    public function test_criacao_sem_nome_titular_e_rejeitada(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->post('/cartoes-credito', $this->dadosCartaoValido([
                'nome_titular' => '   ',
            ]));

        $response->assertSessionHasErrors('nome_titular');
        $this->assertDatabaseCount('cartoes_credito', 0);
    }

    public function test_ultimos_digitos_sao_opcionais(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->post('/cartoes-credito', $this->dadosCartaoValido([
                'ultimos_digitos' => '',
            ]));

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cartoes_credito', [
            'id_usuario' => $usuario->id_usuario,
            'ultimos_digitos' => null,
        ]);
    }

    public function test_ultimos_digitos_com_quantidade_invalida_sao_rejeitados(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->post('/cartoes-credito', $this->dadosCartaoValido([
                'ultimos_digitos' => '12',
            ]));

        $response->assertSessionHasErrors('ultimos_digitos');
        $this->assertDatabaseCount('cartoes_credito', 0);
    }

    public function test_acabamento_invalido_e_rejeitado(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this
            ->actingAs($usuario)
            ->post('/cartoes-credito', $this->dadosCartaoValido([
                'acabamento' => 'dourado',
            ]));

        $response->assertSessionHasErrors('acabamento');
        $this->assertDatabaseCount('cartoes_credito', 0);
    }

    public function test_definir_cartao_padrao_desmarca_o_padrao_anterior(): void
    {
        $usuario = Usuario::factory()->create();
        $cartaoPadraoAtual = $this->criarCartaoCredito($usuario, ['nome' => 'Inter', 'padrao' => SimNao::Sim]);
        $novoCartaoPadrao = $this->criarCartaoCredito($usuario, ['nome' => 'Nubank', 'padrao' => SimNao::Nao]);

        $response = $this
            ->actingAs($usuario)
            ->patch('/cartoes-credito/'.$novoCartaoPadrao->getRouteKey().'/padrao');

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/cartoes-credito');

        $this->assertSame(SimNao::Sim, $novoCartaoPadrao->refresh()->padrao);
        $this->assertSame(SimNao::Nao, $cartaoPadraoAtual->refresh()->padrao);
    }

    public function test_usuario_nao_pode_definir_padrao_em_cartao_de_outro_usuario(): void
    {
        $dono = Usuario::factory()->create();
        $outroUsuario = Usuario::factory()->create();
        $cartao = $this->criarCartaoCredito($dono, ['padrao' => SimNao::Nao]);

        $response = $this
            ->actingAs($outroUsuario)
            ->patch('/cartoes-credito/'.$cartao->getRouteKey().'/padrao');

        $response->assertForbidden();
        $this->assertSame(SimNao::Nao, $cartao->refresh()->padrao);
    }

    public function test_index_envia_lancamentos_recentes_somente_dos_cartoes_ativos_sem_cancelados(): void
    {
        $usuario = Usuario::factory()->create();
        $cartaoAtivo = $this->criarCartaoCredito($usuario, ['nome' => 'Ativo']);
        $cartaoArquivado = $this->criarCartaoCredito($usuario, ['nome' => 'Arquivado', 'arquivada' => SimNao::Sim]);

        $this->criarLancamentoNoCartao($usuario, $cartaoAtivo, 'Supermercado', SituacaoLancamento::Pendente);
        $this->criarLancamentoNoCartao($usuario, $cartaoAtivo, 'Compra cancelada', SituacaoLancamento::Cancelado);
        $this->criarLancamentoNoCartao($usuario, $cartaoArquivado, 'Compra antiga', SituacaoLancamento::Pendente);

        $response = $this
            ->actingAs($usuario)
            ->get('/cartoes-credito');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('CartaoCredito/Index')
            ->has('cartoesCredito', 2)
            ->has('acabamentos', count(AcabamentoVisual::cases()))
            ->has("lancamentosRecentesPorCartao.{$cartaoAtivo->id_cartao_credito}", 1)
            ->where("lancamentosRecentesPorCartao.{$cartaoAtivo->id_cartao_credito}.0.descricao", 'Supermercado')
            ->where("lancamentosRecentesPorCartao.{$cartaoAtivo->id_cartao_credito}.0.situacao_fatura", 'aberta')
            ->missing("lancamentosRecentesPorCartao.{$cartaoArquivado->id_cartao_credito}")
        );
    }

    private function dadosCartaoValido(array $sobrescrever = []): array
    {
        return [
            'nome' => 'Nubank Ultravioleta',
            'nome_titular' => 'Aline Rocha',
            'ultimos_digitos' => '',
            'limite_total' => '12.500,00',
            'dia_fechamento' => 1,
            'dia_vencimento' => 10,
            'bandeira' => BandeiraCartaoCredito::Mastercard->value,
            'acabamento' => AcabamentoVisual::Esmeralda->value,
            'padrao' => 'N',
            ...$sobrescrever,
        ];
    }

    private function criarCartaoCredito(Usuario $usuario, array $sobrescrever = []): CartaoCredito
    {
        return CartaoCredito::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Cartão teste',
            'nome_titular' => 'Titular teste',
            'limite_total' => 5000,
            'dia_fechamento' => 1,
            'dia_vencimento' => 10,
            'bandeira' => BandeiraCartaoCredito::Visa,
            'acabamento' => AcabamentoVisual::Esmeralda,
            'padrao' => SimNao::Nao,
            'arquivada' => SimNao::Nao,
            ...$sobrescrever,
        ]);
    }

    private function criarLancamentoNoCartao(
        Usuario $usuario,
        CartaoCredito $cartao,
        string $descricao,
        SituacaoLancamento $situacao,
    ): Lancamento {
        return Lancamento::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => $descricao,
            'valor' => 150,
            'data_vencimento' => now()->toDateString(),
            'tipo' => TipoLancamento::Despesa,
            'forma_pagamento' => FormaPagamento::CartaoCredito,
            'id_cartao_credito' => $cartao->id_cartao_credito,
            'situacao' => $situacao,
            'eh_recorrencia' => SimNao::Nao,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
        ]);
    }
}
