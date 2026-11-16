<?php

namespace Tests\Feature\EspacoCompartilhado;

use App\Enum\AcaoAuditoriaCompartilhada;
use App\Models\EspacoCompartilhado\AuditoriaEspacoCompartilhado;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PreparaEspacoCompartilhado;
use Tests\TestCase;

class AssumirLancamentoCompartilhadoTest extends TestCase
{
    use PreparaEspacoCompartilhado;
    use RefreshDatabase;

    private Usuario $carolina;

    private Usuario $aline;

    private EspacoCompartilhado $espaco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carolina = $this->criarUsuarioComCompartilhamento('Carolina');
        $this->aline = $this->criarUsuarioComCompartilhamento('Aline');
        $this->espaco = $this->criarEspacoComMembros($this->carolina, $this->aline);
    }

    public function test_membro_assume_lancamento_pendente_e_acao_e_auditada(): void
    {
        $categoriaDaAline = $this->criarCategoriaDeSaidaDoUsuario($this->aline, 'Moradia');
        $lancamento = $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'id_categoria' => $this->criarCategoriaDeSaidaDoUsuario($this->carolina)->id_categoria,
        ]);

        $this->autenticarComo($this->aline)
            ->post($this->rotaDoLancamento('compartilhados.lancamentos.assumir', $this->espaco, $lancamento), [
                'versao' => 1,
                'id_categoria' => $categoriaDaAline->id_categoria,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sucesso');

        $assumido = $lancamento->fresh();
        $auditoria = AuditoriaEspacoCompartilhado::query()
            ->where('acao', AcaoAuditoriaCompartilhada::ResponsavelAlterado)
            ->firstOrFail();

        $this->assertSame($this->aline->id_usuario, $assumido->id_usuario_responsavel);
        $this->assertSame($categoriaDaAline->id_categoria, $assumido->id_categoria);
        $this->assertSame(2, $assumido->versao);
        $this->assertSame($this->aline->id_usuario, $auditoria->id_usuario);
        $this->assertSame($this->carolina->id_usuario, $auditoria->dados_anteriores['id_usuario_responsavel']);
        $this->assertSame('Aline', $auditoria->dados_novos['responsavel_nome']);
    }

    public function test_assumir_sem_categoria_e_recusado(): void
    {
        $lancamento = $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'id_categoria' => $this->criarCategoriaDeSaidaDoUsuario($this->carolina)->id_categoria,
        ]);

        $this->autenticarComo($this->aline)
            ->post($this->rotaDoLancamento('compartilhados.lancamentos.assumir', $this->espaco, $lancamento), [
                'versao' => 1,
            ])
            ->assertSessionHasErrors('id_categoria');

        $this->assertSame($this->carolina->id_usuario, $lancamento->fresh()->id_usuario_responsavel);
    }

    public function test_apos_assumir_o_novo_responsavel_pode_dar_baixa(): void
    {
        $categoriaDaAline = $this->criarCategoriaDeSaidaDoUsuario($this->aline);
        $lancamento = $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'id_categoria' => $this->criarCategoriaDeSaidaDoUsuario($this->carolina)->id_categoria,
        ]);

        $this->autenticarComo($this->aline)
            ->post($this->rotaDoLancamento('compartilhados.lancamentos.assumir', $this->espaco, $lancamento), [
                'versao' => 1,
                'id_categoria' => $categoriaDaAline->id_categoria,
            ]);

        $this->assertTrue($this->aline->can('darBaixa', $lancamento->fresh()));
        $this->assertFalse($this->carolina->can('darBaixa', $lancamento->fresh()));
    }

    public function test_nao_membro_nao_assume_lancamento(): void
    {
        $estranha = $this->criarUsuarioComCompartilhamento('Estranha');
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($estranha);
        $lancamento = $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'id_categoria' => $this->criarCategoriaDeSaidaDoUsuario($this->carolina)->id_categoria,
        ]);

        $this->autenticarComo($estranha)
            ->post($this->rotaDoLancamento('compartilhados.lancamentos.assumir', $this->espaco, $lancamento), [
                'versao' => 1,
                'id_categoria' => $categoria->id_categoria,
            ])
            ->assertForbidden();

        $this->assertSame($this->carolina->id_usuario, $lancamento->fresh()->id_usuario_responsavel);
    }

    public function test_lancamento_pago_nao_pode_ser_assumido(): void
    {
        $categoriaDaAline = $this->criarCategoriaDeSaidaDoUsuario($this->aline);
        $lancamento = LancamentoCompartilhado::factory()
            ->doEspaco($this->espaco)
            ->sobResponsabilidadeDe($this->carolina)
            ->comCategoria($this->criarCategoriaDeSaidaDoUsuario($this->carolina)->id_categoria)
            ->pago()
            ->create();

        $this->autenticarComo($this->aline)
            ->post($this->rotaDoLancamento('compartilhados.lancamentos.assumir', $this->espaco, $lancamento), [
                'versao' => 1,
                'id_categoria' => $categoriaDaAline->id_categoria,
            ])
            ->assertSessionHas('erro');

        $this->assertSame($this->carolina->id_usuario, $lancamento->fresh()->id_usuario_responsavel);
    }

    public function test_assumir_com_versao_desatualizada_e_recusado(): void
    {
        $categoriaDaAline = $this->criarCategoriaDeSaidaDoUsuario($this->aline);
        $lancamento = $this->criarLancamentoCompartilhado($this->espaco, $this->carolina, [
            'versao' => 3,
            'id_categoria' => $this->criarCategoriaDeSaidaDoUsuario($this->carolina)->id_categoria,
        ]);

        $this->autenticarComo($this->aline)
            ->post($this->rotaDoLancamento('compartilhados.lancamentos.assumir', $this->espaco, $lancamento), [
                'versao' => 2,
                'id_categoria' => $categoriaDaAline->id_categoria,
            ])
            ->assertSessionHas('erro');

        $this->assertSame($this->carolina->id_usuario, $lancamento->fresh()->id_usuario_responsavel);
    }
}
