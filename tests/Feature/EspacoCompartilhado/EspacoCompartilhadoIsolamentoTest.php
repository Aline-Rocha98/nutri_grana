<?php

namespace Tests\Feature\EspacoCompartilhado;

use App\Enum\PapelMembroEspaco;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\EspacoCompartilhado\MembroEspacoCompartilhado;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PreparaEspacoCompartilhado;
use Tests\TestCase;

class EspacoCompartilhadoIsolamentoTest extends TestCase
{
    use PreparaEspacoCompartilhado;
    use RefreshDatabase;

    private Usuario $carolina;

    private Usuario $aline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carolina = $this->criarUsuarioComCompartilhamento('Carolina');
        $this->aline = $this->criarUsuarioComCompartilhamento('Aline');
    }

    public function test_usuario_sem_flag_nao_acessa_o_modulo(): void
    {
        $usuarioSemFlag = Usuario::factory()->create();

        $this->autenticarComo($usuarioSemFlag)
            ->get(route('compartilhados.index'))
            ->assertForbidden();
    }

    public function test_criar_espaco_torna_o_criador_administrador(): void
    {
        $this->autenticarComo($this->carolina)
            ->post(route('compartilhados.criar'), ['nome' => 'Casa'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $espaco = EspacoCompartilhado::query()->firstOrFail();
        $vinculo = MembroEspacoCompartilhado::query()->firstOrFail();

        $this->assertSame('Casa', $espaco->nome);
        $this->assertSame($this->carolina->id_usuario, $vinculo->id_usuario);
        $this->assertSame(PapelMembroEspaco::Administrador, $vinculo->papel);
    }

    public function test_nao_membro_nao_acessa_o_painel_do_espaco(): void
    {
        $espaco = $this->criarEspacoComMembros($this->carolina);

        $this->autenticarComo($this->aline)
            ->get(route('compartilhados.show', ['espacoCompartilhado' => $espaco]))
            ->assertForbidden();
    }

    public function test_painel_nao_expoe_dados_financeiros_pessoais_por_padrao(): void
    {
        $espaco = $this->criarEspacoComMembros($this->carolina, $this->aline);
        $this->criarContaBancariaPadrao($this->carolina);
        $this->criarLancamentoCompartilhado($espaco, $this->carolina, ['descricao' => 'Aluguel']);

        $this->autenticarComo($this->aline)
            ->get(route('compartilhados.show', ['espacoCompartilhado' => $espaco]))
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->component('EspacoCompartilhado/Show')
                ->has('lancamentos', 1)
                ->where('lancamentos.0.descricao', 'Aluguel')
                ->where('lancamentos.0.eh_responsavel_usuario_atual', false)
                ->missing('dadosLancamentoPessoal')
                ->missing('contasBancarias')
                ->has('membros', 2)
            );
    }

    public function test_lancamento_de_outro_espaco_nao_e_resolvido_na_rota_aninhada(): void
    {
        $espacoDaAline = $this->criarEspacoComMembros($this->aline);
        $espacoDaCarolina = $this->criarEspacoComMembros($this->carolina);
        $lancamentoDaCarolina = $this->criarLancamentoCompartilhado($espacoDaCarolina, $this->carolina);

        $this->autenticarComo($this->aline)
            ->post($this->rotaDoLancamento('compartilhados.lancamentos.assumir', $espacoDaAline, $lancamentoDaCarolina), [
                'versao' => 1,
                'id_categoria' => $this->criarCategoriaDeSaidaDoUsuario($this->aline)->id_categoria,
            ])
            ->assertNotFound();

        $this->assertSame(
            $this->carolina->id_usuario,
            LancamentoCompartilhado::query()->findOrFail($lancamentoDaCarolina->id_lancamento_compartilhado)->id_usuario_responsavel
        );
    }

    public function test_membro_comum_nao_pode_arquivar_o_espaco(): void
    {
        $espaco = $this->criarEspacoComMembros($this->carolina, $this->aline);

        $this->autenticarComo($this->aline)
            ->patch(route('compartilhados.arquivar', ['espacoCompartilhado' => $espaco]))
            ->assertForbidden();
    }

    public function test_ultimo_administrador_nao_pode_sair_com_outros_membros_ativos(): void
    {
        $espaco = $this->criarEspacoComMembros($this->carolina, $this->aline);

        $this->autenticarComo($this->carolina)
            ->post(route('compartilhados.sair', ['espacoCompartilhado' => $espaco]))
            ->assertSessionHas('erro');

        $this->assertTrue(
            MembroEspacoCompartilhado::query()
                ->where('id_usuario', $this->carolina->id_usuario)
                ->where('status', 'ativo')
                ->exists()
        );
    }

    public function test_exclusao_da_conta_promove_outro_membro_a_administrador(): void
    {
        $espaco = $this->criarEspacoComMembros($this->carolina, $this->aline);

        $this->autenticarComo($this->carolina)
            ->delete(route('usuario.excluir'), ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $vinculoDaAline = MembroEspacoCompartilhado::query()
            ->where('id_espaco_compartilhado', $espaco->id_espaco_compartilhado)
            ->where('id_usuario', $this->aline->id_usuario)
            ->firstOrFail();

        $this->assertSame(PapelMembroEspaco::Administrador, $vinculoDaAline->papel);
    }
}
