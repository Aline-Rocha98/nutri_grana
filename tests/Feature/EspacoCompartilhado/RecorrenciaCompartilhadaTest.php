<?php

namespace Tests\Feature\EspacoCompartilhado;

use App\Enum\FrequenciaRecorrencia;
use App\Enum\StatusLancamentoCompartilhado;
use App\Enum\StatusRecorrenciaCompartilhada;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\EspacoCompartilhado\RecorrenciaCompartilhada;
use App\Models\Usuario\Usuario;
use App\Services\EspacoCompartilhado\RecorrenciaCompartilhadaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\PreparaEspacoCompartilhado;
use Tests\TestCase;

class RecorrenciaCompartilhadaTest extends TestCase
{
    use PreparaEspacoCompartilhado;
    use RefreshDatabase;

    private Usuario $carolina;

    private EspacoCompartilhado $espaco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2027, 1, 10));
        $this->carolina = $this->criarUsuarioComCompartilhamento('Carolina');
        $this->espaco = $this->criarEspacoComMembros($this->carolina);
    }

    public function test_criar_recorrente_gera_apenas_a_primeira_ocorrencia(): void
    {
        $this->criarRecorrenciaMensal('2027-01-31')->assertSessionHasNoErrors();

        $this->assertSame(1, RecorrenciaCompartilhada::query()->count());
        $this->assertSame(['2027-01-31'], $this->listarDatasDasOcorrencias());
    }

    public function test_ocorrencias_mensais_nao_acumulam_deslocamento_no_fim_do_mes(): void
    {
        $this->criarRecorrenciaMensal('2027-01-31');

        $this->gerarOcorrenciasAte(2027, 4);

        $this->assertSame(
            ['2027-01-31', '2027-02-28', '2027-03-31', '2027-04-30'],
            $this->listarDatasDasOcorrencias(),
        );
    }

    public function test_abrir_o_mesmo_mes_duas_vezes_nao_duplica_ocorrencias(): void
    {
        $this->criarRecorrenciaMensal('2027-01-15');

        $this->gerarOcorrenciasAte(2027, 3);
        $this->gerarOcorrenciasAte(2027, 3);

        $this->autenticarComo($this->carolina)
            ->get(route('compartilhados.show', ['espacoCompartilhado' => $this->espaco, 'ano' => 2027, 'mes' => 3]))
            ->assertOk();

        $this->assertSame(['2027-01-15', '2027-02-15', '2027-03-15'], $this->listarDatasDasOcorrencias());
    }

    public function test_recorrencia_respeita_a_data_final(): void
    {
        $this->criarRecorrenciaMensal('2027-01-10', ['recorrencia_ate' => '2027-02-28']);

        $this->gerarOcorrenciasAte(2027, 6);

        $this->assertSame(['2027-01-10', '2027-02-10'], $this->listarDatasDasOcorrencias());
    }

    public function test_excluir_ocorrencia_a_cancela_sem_regerar(): void
    {
        $this->criarRecorrenciaMensal('2027-01-10');
        $this->gerarOcorrenciasAte(2027, 2);

        $ocorrenciaDeFevereiro = $this->buscarOcorrenciaNaData('2027-02-10');

        $this->autenticarComo($this->carolina)
            ->delete($this->rotaDoLancamento('compartilhados.lancamentos.excluir', $this->espaco, $ocorrenciaDeFevereiro))
            ->assertSessionHasNoErrors();

        $this->gerarOcorrenciasAte(2027, 2);

        $this->assertSame(StatusLancamentoCompartilhado::Cancelado, $ocorrenciaDeFevereiro->fresh()->status);
        $this->assertSame(1, LancamentoCompartilhado::query()->whereDate('data_vencimento', '2027-02-10')->count());
    }

    public function test_encerrar_recorrencia_cancela_futuras_e_para_de_gerar(): void
    {
        $this->criarRecorrenciaMensal('2027-01-10');
        $this->gerarOcorrenciasAte(2027, 4);

        $ocorrenciaDeMarco = $this->buscarOcorrenciaNaData('2027-03-10');

        $this->autenticarComo($this->carolina)
            ->delete($this->rotaDoLancamento('compartilhados.lancamentos.excluir', $this->espaco, $ocorrenciaDeMarco), [
                'encerrar_recorrencia' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->gerarOcorrenciasAte(2027, 8);

        $this->assertSame(
            StatusRecorrenciaCompartilhada::Encerrada,
            RecorrenciaCompartilhada::query()->firstOrFail()->status
        );
        $this->assertSame(['2027-01-10', '2027-02-10'], $this->listarDatasDasOcorrencias(StatusLancamentoCompartilhado::Pendente));
        $this->assertSame(
            StatusLancamentoCompartilhado::Cancelado,
            $this->buscarOcorrenciaNaData('2027-04-10')->status
        );
    }

    private function criarRecorrenciaMensal(string $dataPrimeiraOcorrencia, array $dadosExtras = []): TestResponse
    {
        $categoria = $this->criarCategoriaDeSaidaDoUsuario($this->carolina);

        return $this->autenticarComo($this->carolina)
            ->post(route('compartilhados.lancamentos.criar', ['espacoCompartilhado' => $this->espaco]), [
                'descricao' => 'Internet',
                'valor' => '120,00',
                'data_vencimento' => $dataPrimeiraOcorrencia,
                'id_usuario_responsavel' => $this->carolina->id_usuario,
                'id_categoria' => $categoria->id_categoria,
                'recorrente' => true,
                'frequencia_recorrencia' => FrequenciaRecorrencia::Mensal->value,
                ...$dadosExtras,
            ]);
    }

    private function gerarOcorrenciasAte(int $ano, int $mes): void
    {
        app(RecorrenciaCompartilhadaService::class)->gerarOcorrenciasAteOFimDoMes(
            (int) $this->espaco->id_espaco_compartilhado,
            $ano,
            $mes
        );
    }

    private function buscarOcorrenciaNaData(string $data): LancamentoCompartilhado
    {
        return LancamentoCompartilhado::query()->whereDate('data_vencimento', $data)->firstOrFail();
    }

    private function listarDatasDasOcorrencias(?StatusLancamentoCompartilhado $status = null): array
    {
        return LancamentoCompartilhado::query()
            ->whereNotNull('id_recorrencia_compartilhada')
            ->when($status, fn ($consulta) => $consulta->where('status', $status))
            ->orderBy('data_vencimento')
            ->get()
            ->map(fn (LancamentoCompartilhado $lancamento) => $lancamento->data_vencimento->toDateString())
            ->all();
    }
}
