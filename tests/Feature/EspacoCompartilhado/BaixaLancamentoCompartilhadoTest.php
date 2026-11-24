<?php

namespace Tests\Feature\EspacoCompartilhado;

use App\Enum\FormaPagamento;
use App\Enum\StatusLancamentoCompartilhado;
use App\Models\Categoria\Categoria;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\Usuario\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\PreparaEspacoCompartilhado;
use Tests\TestCase;

class BaixaLancamentoCompartilhadoTest extends TestCase
{
    use PreparaEspacoCompartilhado;
    use RefreshDatabase;

    private Usuario $carolina;

    private Usuario $aline;

    private EspacoCompartilhado $espaco;

    private ContaBancaria $contaDaCarolina;

    private Categoria $categoriaDaCarolina;

    private LancamentoCompartilhado $lancamento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 11, 13));
        $this->carolina = $this->criarUsuarioComCompartilhamento('Carolina');
        $this->aline = $this->criarUsuarioComCompartilhamento('Aline');
        $this->espaco = $this->criarEspacoComMembros($this->carolina, $this->aline);
        $this->contaDaCarolina = $this->criarContaBancariaPadrao($this->carolina);
        $this->categoriaDaCarolina = $this->criarCategoriaDeSaida($this->carolina, 'Moradia');
        $this->lancamento = $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'descricao' => 'Aluguel',
            'valor' => '1500.00',
            'id_categoria' => $this->categoriaDaCarolina->id_categoria,
        ]);
    }

    public function test_responsavel_da_baixa_grava_pagamento_no_compartilhado_sem_criar_pessoal(): void
    {
        $this->darBaixaComo($this->carolina)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sucesso');

        $lancamentoBaixado = $this->lancamento->fresh();

        $this->assertSame(StatusLancamentoCompartilhado::Pago, $lancamentoBaixado->status);
        $this->assertSame($this->carolina->id_usuario, $lancamentoBaixado->id_usuario_baixa);
        $this->assertSame(2, $lancamentoBaixado->versao);
        $this->assertSame(FormaPagamento::ContaBancaria, $lancamentoBaixado->forma_pagamento);
        $this->assertSame($this->contaDaCarolina->id_conta_bancaria, $lancamentoBaixado->id_conta_bancaria);
        $this->assertSame($this->categoriaDaCarolina->id_categoria, $lancamentoBaixado->id_categoria);
        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_valor_e_tipo_enviados_pelo_cliente_sao_ignorados(): void
    {
        $this->darBaixaComo($this->carolina, [
            'valor' => '1,00',
            'tipo' => 'receita',
        ])->assertSessionHasNoErrors();

        $lancamentoBaixado = $this->lancamento->fresh();

        $this->assertSame('1500.00', (string) $lancamentoBaixado->valor);
        $this->assertSame(StatusLancamentoCompartilhado::Pago, $lancamentoBaixado->status);
        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_membro_que_nao_e_responsavel_nao_da_baixa(): void
    {
        $this->darBaixaComo($this->aline)
            ->assertSessionHas('erro', 'Este lançamento pertence a Carolina. Para dar baixa, transfira a responsabilidade para você.');

        $this->assertSame(StatusLancamentoCompartilhado::Pendente, $this->lancamento->fresh()->status);
        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_conta_bancaria_de_outro_usuario_e_recusada_e_nada_e_gravado(): void
    {
        $contaDaAline = $this->criarContaBancariaPadrao($this->aline);

        $this->darBaixaComo($this->carolina, ['id_conta_bancaria' => $contaDaAline->id_conta_bancaria])
            ->assertSessionHasErrors('id_conta_bancaria');

        $this->assertSame(StatusLancamentoCompartilhado::Pendente, $this->lancamento->fresh()->status);
        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_estorno_volta_para_pendente_e_limpa_pagamento(): void
    {
        $this->darBaixaComo($this->carolina)->assertSessionHasNoErrors();

        $lancamentoBaixado = $this->lancamento->fresh();

        $this->autenticarComo($this->carolina)
            ->post($this->rotaDoLancamento('compartilhados.lancamentos.estornar-baixa', $this->espaco, $lancamentoBaixado), [
                'versao' => $lancamentoBaixado->versao,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sucesso');

        $lancamentoEstornado = $this->lancamento->fresh();

        $this->assertSame(StatusLancamentoCompartilhado::Pendente, $lancamentoEstornado->status);
        $this->assertNull($lancamentoEstornado->forma_pagamento);
        $this->assertNull($lancamentoEstornado->id_conta_bancaria);
        $this->assertNull($lancamentoEstornado->id_cartao_credito);
        $this->assertNull($lancamentoEstornado->data_pagamento);
        $this->assertNull($lancamentoEstornado->id_usuario_baixa);
        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_somente_responsavel_estorna_a_baixa(): void
    {
        $this->darBaixaComo($this->carolina)->assertSessionHasNoErrors();
        $lancamentoBaixado = $this->lancamento->fresh();

        $this->autenticarComo($this->aline)
            ->post($this->rotaDoLancamento('compartilhados.lancamentos.estornar-baixa', $this->espaco, $lancamentoBaixado), [
                'versao' => $lancamentoBaixado->versao,
            ])
            ->assertSessionHas('erro');

        $this->assertSame(StatusLancamentoCompartilhado::Pago, $this->lancamento->fresh()->status);
    }

    private function criarCategoriaDeSaida(Usuario $usuario, string $nome): Categoria
    {
        return $this->criarCategoriaDeSaidaDoUsuario($usuario, $nome);
    }

    private function darBaixaComo(Usuario $usuario, array $dadosExtras = []): TestResponse
    {
        return $this->autenticarComo($usuario)
            ->post($this->rotaDoLancamento('compartilhados.lancamentos.dar-baixa', $this->espaco, $this->lancamento), [
                'versao' => 1,
                'descricao' => 'Aluguel',
                'data_vencimento' => '2026-11-13',
                'forma_pagamento' => FormaPagamento::ContaBancaria->value,
                'id_conta_bancaria' => $this->contaDaCarolina->id_conta_bancaria,
                'id_categoria' => $this->categoriaDaCarolina->id_categoria,
                'situacao' => 'pago',
                ...$dadosExtras,
            ]);
    }
}
