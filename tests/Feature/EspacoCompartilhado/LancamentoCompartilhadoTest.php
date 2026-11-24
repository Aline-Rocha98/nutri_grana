<?php

namespace Tests\Feature\EspacoCompartilhado;

use App\Enum\AcaoAuditoriaCompartilhada;
use App\Enum\StatusLancamentoCompartilhado;
use App\Models\EspacoCompartilhado\AuditoriaEspacoCompartilhado;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\Usuario\Usuario;
use Carbon\Carbon;
use Database\Factories\EspacoCompartilhadoFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PreparaEspacoCompartilhado;
use Tests\TestCase;

class LancamentoCompartilhadoTest extends TestCase
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

    public function test_membro_cria_lancamento_com_responsavel_do_espaco(): void
    {
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);

        $this->autenticarComo($this->aline)
            ->post(route('compartilhados.lancamentos.criar', ['espacoCompartilhado' => $this->espaco]), [
                'descricao' => 'Aluguel',
                'valor' => '1.234,56',
                'data_vencimento' => '2026-11-20',
                'id_usuario_responsavel' => $this->carolina->id_usuario,
                'id_categoria' => $categoria->id_categoria,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sucesso');

        $lancamento = LancamentoCompartilhado::query()->firstOrFail();

        $this->assertSame('1234.56', (string) $lancamento->valor);
        $this->assertSame($this->carolina->id_usuario, $lancamento->id_usuario_responsavel);
        $this->assertSame($categoria->id_categoria, $lancamento->id_categoria);
        $this->assertSame($this->aline->id_usuario, $lancamento->criado_por);
        $this->assertSame(StatusLancamentoCompartilhado::Pendente, $lancamento->status);
        $this->assertTrue(
            AuditoriaEspacoCompartilhado::query()->where('acao', AcaoAuditoriaCompartilhada::LancamentoCriado)->exists()
        );
    }

    public function test_categoria_de_outro_usuario_e_recusada_no_create(): void
    {
        $categoriaDaAline = $this->criarCategoriaDeSaidaDoUsuario($this->aline);

        $this->autenticarComo($this->aline)
            ->post(route('compartilhados.lancamentos.criar', ['espacoCompartilhado' => $this->espaco]), [
                'descricao' => 'Aluguel',
                'valor' => '100,00',
                'data_vencimento' => '2026-11-20',
                'id_usuario_responsavel' => $this->carolina->id_usuario,
                'id_categoria' => $categoriaDaAline->id_categoria,
            ])
            ->assertSessionHasErrors('id_categoria');

        $this->assertDatabaseCount('lancamentos_compartilhados', 0);
    }

    public function test_responsavel_precisa_ser_membro_ativo_do_espaco(): void
    {
        $estranha = $this->criarUsuarioComCompartilhamento('Estranha');
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($estranha);

        $this->autenticarComo($this->aline)
            ->post(route('compartilhados.lancamentos.criar', ['espacoCompartilhado' => $this->espaco]), [
                'descricao' => 'Mercado',
                'valor' => '100,00',
                'data_vencimento' => '2026-11-20',
                'id_usuario_responsavel' => $estranha->id_usuario,
                'id_categoria' => $categoria->id_categoria,
            ])
            ->assertSessionHasErrors('id_usuario_responsavel');

        $this->assertDatabaseCount('lancamentos_compartilhados', 0);
    }

    public function test_atualizacao_com_versao_desatualizada_e_recusada(): void
    {
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);
        $lancamento = $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'versao' => 2,
            'id_categoria' => $categoria->id_categoria,
        ]);

        $this->autenticarComo($this->carolina)
            ->put($this->rotaDoLancamento('compartilhados.lancamentos.atualizar', $this->espaco, $lancamento), [
                'versao' => 1,
                'descricao' => 'Aluguel atualizado',
                'valor' => '200,00',
                'data_vencimento' => '2026-11-20',
                'id_usuario_responsavel' => $this->carolina->id_usuario,
                'id_categoria' => $categoria->id_categoria,
            ])
            ->assertSessionHasErrors('versao')
            ->assertSessionHas('erro');

        $this->assertNotSame('Aluguel atualizado', $lancamento->fresh()->descricao);
    }

    public function test_atualizacao_incrementa_versao_e_audita_troca_de_responsavel(): void
    {
        $categoriaDaCarolina = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);
        $categoriaDaAline = $this->criarCategoriaDeSaidaDoUsuario($this->aline);
        $lancamento = $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'id_categoria' => $categoriaDaCarolina->id_categoria,
        ]);

        $this->autenticarComo($this->carolina)
            ->put($this->rotaDoLancamento('compartilhados.lancamentos.atualizar', $this->espaco, $lancamento), [
                'versao' => 1,
                'descricao' => 'Internet',
                'valor' => '99,90',
                'data_vencimento' => '2026-11-25',
                'id_usuario_responsavel' => $this->aline->id_usuario,
                'id_categoria' => $categoriaDaAline->id_categoria,
            ])
            ->assertSessionHasNoErrors();

        $atualizado = $lancamento->fresh();

        $this->assertSame(2, $atualizado->versao);
        $this->assertSame($this->aline->id_usuario, $atualizado->id_usuario_responsavel);
        $this->assertSame($categoriaDaAline->id_categoria, $atualizado->id_categoria);
        $this->assertTrue(
            AuditoriaEspacoCompartilhado::query()->where('acao', AcaoAuditoriaCompartilhada::ResponsavelAlterado)->exists()
        );
    }

    public function test_troca_de_responsavel_sem_categoria_nova_e_recusada(): void
    {
        $categoriaDaCarolina = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);
        $lancamento = $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'id_categoria' => $categoriaDaCarolina->id_categoria,
        ]);

        $this->autenticarComo($this->carolina)
            ->put($this->rotaDoLancamento('compartilhados.lancamentos.atualizar', $this->espaco, $lancamento), [
                'versao' => 1,
                'descricao' => 'Internet',
                'valor' => '99,90',
                'data_vencimento' => '2026-11-25',
                'id_usuario_responsavel' => $this->aline->id_usuario,
                'id_categoria' => $categoriaDaCarolina->id_categoria,
            ])
            ->assertSessionHasErrors('id_categoria');

        $this->assertSame($this->carolina->id_usuario, $lancamento->fresh()->id_usuario_responsavel);
    }

    public function test_membro_que_nao_criou_nem_e_responsavel_nao_edita(): void
    {
        $bruna = $this->criarUsuarioComCompartilhamento('Bruna');
        EspacoCompartilhadoFactory::vincularMembroAoEspaco($this->espaco, $bruna);
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->aline);
        $lancamento = $this->criarLancamentoCompartilhado($this->espaco, $this->aline, [
            'criado_por' => $this->aline->id_usuario,
            'id_categoria' => $categoria->id_categoria,
        ]);

        $this->autenticarComo($bruna)
            ->put($this->rotaDoLancamento('compartilhados.lancamentos.atualizar', $this->espaco, $lancamento), [
                'versao' => 1,
                'descricao' => 'Alterado',
                'valor' => '10,00',
                'data_vencimento' => '2026-11-25',
                'id_usuario_responsavel' => $bruna->id_usuario,
                'id_categoria' => $this->criarCategoriaDeSaidaDoUsuario($bruna)->id_categoria,
            ])
            ->assertForbidden();
    }

    public function test_lancamento_pago_nao_pode_ser_excluido(): void
    {
        $lancamento = LancamentoCompartilhado::factory()
            ->doEspaco($this->espaco)
            ->sobResponsabilidadeDe($this->carolina)
            ->pago()
            ->create();

        $this->autenticarComo($this->carolina)
            ->delete($this->rotaDoLancamento('compartilhados.lancamentos.excluir', $this->espaco, $lancamento))
            ->assertSessionHas('erro');

        $this->assertNotNull($lancamento->fresh());
    }

    public function test_resumo_do_mes_separa_pago_pendente_e_vencido_por_membro(): void
    {
        $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'valor' => '100.10',
            'data_vencimento' => '2026-11-05',
        ]);
        $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'valor' => '50.20',
            'data_vencimento' => '2026-11-25',
        ]);
        LancamentoCompartilhado::factory()
            ->doEspaco($this->espaco)
            ->sobResponsabilidadeDe($this->aline)
            ->pago()
            ->create(['valor' => '0.30', 'data_vencimento' => '2026-11-10']);

        $this->autenticarComo($this->aline)
            ->get(route('compartilhados.show', ['espacoCompartilhado' => $this->espaco, 'ano' => 2026, 'mes' => 11]))
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->where('resumo.total', '150,60')
                ->where('resumo.pago', '0,30')
                ->where('resumo.vencido', '100,10')
                ->where('resumo.a_vencer', '50,20')
                ->where('resumo.quantidade_vencidos', 1)
                ->has('resumo.por_membro', 2)
                ->where('resumo.por_membro.0.nome', 'Carolina')
                ->where('resumo.por_membro.0.pendente', '150,30')
                ->where('resumo.por_membro.1.nome', 'Aline')
                ->where('resumo.por_membro.1.pago', '0,30')
            );
    }
}
