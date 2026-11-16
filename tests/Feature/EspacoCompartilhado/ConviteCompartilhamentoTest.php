<?php

namespace Tests\Feature\EspacoCompartilhado;

use App\Enum\StatusConviteCompartilhamento;
use App\Models\EspacoCompartilhado\ConviteCompartilhamento;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\Usuario\Usuario;
use App\Repositories\EspacoCompartilhado\MembroEspacoCompartilhadoRepository;
use Database\Factories\EspacoCompartilhadoFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\PreparaEspacoCompartilhado;
use Tests\TestCase;

class ConviteCompartilhamentoTest extends TestCase
{
    use PreparaEspacoCompartilhado;
    use RefreshDatabase;

    private const CODIGO_ERRADO = 'AAAA-AAAA';

    private Usuario $carolina;

    private Usuario $aline;

    private EspacoCompartilhado $espaco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carolina = $this->criarUsuarioComCompartilhamento('Carolina');
        $this->aline = $this->criarUsuarioComCompartilhamento('Aline');
        $this->espaco = $this->criarEspacoComMembros($this->carolina);
    }

    public function test_convite_gera_codigo_temporario_gravado_apenas_como_hash(): void
    {
        $this->freezeTime();

        $codigo = $this->enviarConviteParaAline();
        $convite = $this->buscarUltimoConvite();

        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $codigo);
        $this->assertSame(64, strlen((string) $convite->codigo_hash));
        $this->assertStringNotContainsString(str_replace('-', '', $codigo), (string) $convite->codigo_hash);
        $this->assertSame((int) $this->aline->id_usuario, (int) $convite->id_usuario_convidado);
        $this->assertSame(StatusConviteCompartilhamento::Pendente, $convite->status);
        $this->assertSame(0, $convite->tentativas_invalidas);
        $this->assertEqualsWithDelta(15 * 60, now()->diffInSeconds($convite->expira_em), 1);

        $codigoNaSessao = session('codigoConvite');
        $this->assertSame('Aline', $codigoNaSessao['nome_convidado']);
        $this->assertSame(mb_strtolower($this->aline->email), $codigoNaSessao['email_convidado']);
    }

    public function test_nao_convida_email_sem_cadastro(): void
    {
        $this->autenticarComo($this->carolina)
            ->post($this->rotaEnviarConvite(), ['email' => 'ninguem@exemplo.com'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('convites_compartilhamento', 0);
    }

    public function test_nao_convida_usuario_sem_modulo_habilitado(): void
    {
        $semModulo = Usuario::factory()->create(['nome' => 'Sem módulo']);

        $this->autenticarComo($this->carolina)
            ->post($this->rotaEnviarConvite(), ['email' => $semModulo->email])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('convites_compartilhamento', 0);
    }

    public function test_nao_convida_o_proprio_email(): void
    {
        $this->autenticarComo($this->carolina)
            ->post($this->rotaEnviarConvite(), ['email' => $this->carolina->email])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('convites_compartilhamento', 0);
    }

    public function test_nao_convida_quem_ja_e_membro(): void
    {
        EspacoCompartilhadoFactory::vincularMembroAoEspaco($this->espaco, $this->aline);

        $this->autenticarComo($this->carolina)
            ->post($this->rotaEnviarConvite(), ['email' => $this->aline->email])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('convites_compartilhamento', 0);
    }

    public function test_membro_comum_nao_pode_convidar(): void
    {
        EspacoCompartilhadoFactory::vincularMembroAoEspaco($this->espaco, $this->aline);
        $bruna = $this->criarUsuarioComCompartilhamento('Bruna');

        $this->autenticarComo($this->aline)
            ->post($this->rotaEnviarConvite(), ['email' => $bruna->email])
            ->assertForbidden();

        $this->assertDatabaseCount('convites_compartilhamento', 0);
    }

    public function test_convidada_aceita_com_email_do_convidante_e_codigo(): void
    {
        $codigo = $this->enviarConviteParaAline();
        $convite = $this->buscarUltimoConvite();

        $this->autenticarComo($this->aline)
            ->post($this->rotaAceitar($convite), [
                'email_convidante' => strtoupper($this->carolina->email),
                'codigo' => strtolower(str_replace('-', '', $codigo)),
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sucesso')
            ->assertRedirectContains('/compartilhados/');

        $this->assertTrue($this->alineEhMembroAtiva());
        $this->assertSame(StatusConviteCompartilhamento::Aceito, $convite->fresh()->status);
    }

    public function test_email_do_convidante_errado_conta_como_tentativa_invalida(): void
    {
        $codigo = $this->enviarConviteParaAline();
        $convite = $this->buscarUltimoConvite();

        $this->autenticarComo($this->aline)
            ->post($this->rotaAceitar($convite), [
                'email_convidante' => 'outra@exemplo.com',
                'codigo' => $codigo,
            ])
            ->assertSessionHasErrors('codigo');

        $this->assertFalse($this->alineEhMembroAtiva());
        $this->assertSame(1, $convite->fresh()->tentativas_invalidas);
    }

    public function test_cinco_tentativas_erradas_bloqueiam_o_convite(): void
    {
        $codigo = $this->enviarConviteParaAline();
        $convite = $this->buscarUltimoConvite();

        for ($tentativa = 1; $tentativa <= ConviteCompartilhamento::LIMITE_TENTATIVAS_CODIGO; $tentativa++) {
            $this->autenticarComo($this->aline)
                ->post($this->rotaAceitar($convite), [
                    'email_convidante' => $this->carolina->email,
                    'codigo' => self::CODIGO_ERRADO,
                ])
                ->assertSessionHasErrors('codigo');
        }

        $conviteBloqueado = $convite->fresh();
        $this->assertSame(ConviteCompartilhamento::LIMITE_TENTATIVAS_CODIGO, $conviteBloqueado->tentativas_invalidas);
        $this->assertSame(StatusConviteCompartilhamento::Expirado, $conviteBloqueado->status);

        $this->travel(2)->minutes();

        $this->autenticarComo($this->aline)
            ->post($this->rotaAceitar($convite), [
                'email_convidante' => $this->carolina->email,
                'codigo' => $codigo,
            ])
            ->assertSessionHas('erro');

        $this->assertFalse($this->alineEhMembroAtiva());
    }

    public function test_codigo_expira_apos_quinze_minutos(): void
    {
        $codigo = $this->enviarConviteParaAline();
        $convite = $this->buscarUltimoConvite();

        $this->travel(16)->minutes();

        $this->autenticarComo($this->aline)
            ->post($this->rotaAceitar($convite), [
                'email_convidante' => $this->carolina->email,
                'codigo' => $codigo,
            ])
            ->assertSessionHasErrors('codigo');

        $this->assertFalse($this->alineEhMembroAtiva());
        $this->assertSame(StatusConviteCompartilhamento::Expirado, $convite->fresh()->status);
    }

    public function test_reenviar_convite_invalida_o_codigo_anterior(): void
    {
        $codigoAntigo = $this->enviarConviteParaAline();
        $conviteAntigo = $this->buscarUltimoConvite();

        $codigoNovo = $this->enviarConviteParaAline();
        $conviteNovo = $this->buscarUltimoConvite();

        $this->assertNotSame($conviteAntigo->id_convite_compartilhamento, $conviteNovo->id_convite_compartilhamento);
        $this->assertSame(StatusConviteCompartilhamento::Expirado, $conviteAntigo->fresh()->status);

        $this->autenticarComo($this->aline)
            ->post($this->rotaAceitar($conviteAntigo), [
                'email_convidante' => $this->carolina->email,
                'codigo' => $codigoAntigo,
            ])
            ->assertSessionHas('erro');

        $this->assertFalse($this->alineEhMembroAtiva());

        $this->autenticarComo($this->aline)
            ->post($this->rotaAceitar($conviteNovo), [
                'email_convidante' => $this->carolina->email,
                'codigo' => $codigoNovo,
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($this->alineEhMembroAtiva());
    }

    public function test_outro_usuario_nao_responde_convite_alheio(): void
    {
        $codigo = $this->enviarConviteParaAline();
        $convite = $this->buscarUltimoConvite();
        $intrusa = $this->criarUsuarioComCompartilhamento('Intrusa');

        $this->autenticarComo($intrusa)
            ->post($this->rotaAceitar($convite), [
                'email_convidante' => $this->carolina->email,
                'codigo' => $codigo,
            ])
            ->assertForbidden();

        $this->autenticarComo($intrusa)
            ->post(route('compartilhados.convites.recusar', ['conviteCompartilhamento' => $convite]))
            ->assertForbidden();
    }

    public function test_convidada_pode_recusar_convite(): void
    {
        $this->enviarConviteParaAline();
        $convite = $this->buscarUltimoConvite();

        $this->autenticarComo($this->aline)
            ->post(route('compartilhados.convites.recusar', ['conviteCompartilhamento' => $convite]))
            ->assertSessionHas('sucesso');

        $this->assertSame(StatusConviteCompartilhamento::Recusado, $convite->fresh()->status);
        $this->assertFalse($this->alineEhMembroAtiva());
    }

    public function test_convite_recebido_mostra_nome_do_convidante_sem_expor_email(): void
    {
        $this->enviarConviteParaAline();

        $this->autenticarComo($this->aline)
            ->get(route('compartilhados.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $pagina) => $pagina
                ->component('EspacoCompartilhado/Index')
                ->has('convitesRecebidos', 1)
                ->where('convitesRecebidos.0.nome_convidante', 'Carolina')
                ->where('convitesRecebidos.0.nome_espaco', 'Casa')
                ->missing('convitesRecebidos.0.email_convidante')
            );
    }

    private function enviarConviteParaAline(): string
    {
        $this->autenticarComo($this->carolina)
            ->post($this->rotaEnviarConvite(), ['email' => $this->aline->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('codigoConvite');

        return session('codigoConvite')['codigo'];
    }

    private function buscarUltimoConvite(): ConviteCompartilhamento
    {
        return ConviteCompartilhamento::query()
            ->orderByDesc('id_convite_compartilhamento')
            ->firstOrFail();
    }

    private function rotaEnviarConvite(): string
    {
        return route('compartilhados.convites.enviar', ['espacoCompartilhado' => $this->espaco]);
    }

    private function rotaAceitar(ConviteCompartilhamento $convite): string
    {
        return route('compartilhados.convites.aceitar', ['conviteCompartilhamento' => $convite]);
    }

    private function alineEhMembroAtiva(): bool
    {
        return app(MembroEspacoCompartilhadoRepository::class)->usuarioEhMembroAtivoDoEspaco(
            (int) $this->espaco->id_espaco_compartilhado,
            (int) $this->aline->id_usuario
        );
    }
}
