<?php

namespace Tests\Feature\EspacoCompartilhado;

use App\Enum\StatusConviteCompartilhamento;
use App\Models\EspacoCompartilhado\ConviteCompartilhamento;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\PreparaEspacoCompartilhado;
use Tests\TestCase;

class AvisoConvitesPendentesTest extends TestCase
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
        $this->espaco = $this->criarEspacoComMembros($this->carolina);
    }

    public function test_apos_login_convites_pendentes_sao_exibidos_uma_unica_vez(): void
    {
        $this->criarConvitePendenteParaAline();

        $this->post('/login', ['email' => $this->aline->email, 'password' => 'password'])
            ->assertRedirect(route('home', absolute: false));

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $pagina) => $pagina
                ->has('convitesPendentesAoEntrar', 1)
                ->where('convitesPendentesAoEntrar.0.nome_convidante', 'Carolina')
                ->where('convitesPendentesAoEntrar.0.nome_espaco', 'Casa')
                ->missing('convitesPendentesAoEntrar.0.email_convidante')
            );

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $pagina) => $pagina
                ->has('convitesPendentesAoEntrar', 0)
            );
    }

    public function test_login_sem_convites_pendentes_nao_exibe_aviso(): void
    {
        $this->post('/login', ['email' => $this->aline->email, 'password' => 'password']);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $pagina) => $pagina
                ->has('convitesPendentesAoEntrar', 0)
            );
    }

    public function test_usuario_sem_modulo_habilitado_nao_recebe_aviso(): void
    {
        $semModulo = Usuario::factory()->create();

        $this->post('/login', ['email' => $semModulo->email, 'password' => 'password']);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $pagina) => $pagina
                ->has('convitesPendentesAoEntrar', 0)
            );
    }

    public function test_sessao_ja_autenticada_sem_novo_login_nao_exibe_aviso(): void
    {
        $this->criarConvitePendenteParaAline();

        $this->autenticarComo($this->aline)
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $pagina) => $pagina
                ->has('convitesPendentesAoEntrar', 0)
            );
    }

    private function criarConvitePendenteParaAline(): ConviteCompartilhamento
    {
        return ConviteCompartilhamento::query()->create([
            'id_espaco_compartilhado' => $this->espaco->id_espaco_compartilhado,
            'id_usuario_convidado' => $this->aline->id_usuario,
            'email_destinatario' => mb_strtolower($this->aline->email),
            'codigo_hash' => hash_hmac('sha256', 'ABCD2345', (string) config('app.key')),
            'tentativas_invalidas' => 0,
            'criado_por' => $this->carolina->id_usuario,
            'expira_em' => now()->addMinutes(15),
            'status' => StatusConviteCompartilhamento::Pendente,
        ]);
    }
}
