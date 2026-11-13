<?php

namespace Tests\Feature;

use App\Enum\FormaPagamento;
use App\Enum\FrequenciaRecorrencia;
use App\Enum\SimNao;
use App\Enum\TipoContaBancaria;
use App\Enum\TipoLancamento;
use App\Http\Middleware\EnsureAbsoluteSessionTimeout;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Usuario\Usuario;
use App\Services\Lancamento\RecorrenciaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LancamentoRecorrenciaTest extends TestCase
{
    use RefreshDatabase;

    private const URL_LANCAMENTOS = '/lancamentos';

    private const DATA_PRIMEIRO_LANCAMENTO = '2026-11-10';

    private Usuario $usuario;

    private ContaBancaria $conta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 11, 5));
        $this->usuario = Usuario::factory()->create();
        $this->conta = $this->criarContaBancaria();
    }

    public function test_quantidade_de_ocorrencias_define_a_data_da_ultima_ocorrencia(): void
    {
        $this->criarLancamentoRecorrente(['quantidade_ocorrencias' => 3])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('erro');

        $this->assertSame('2027-01-10', $this->buscarLancamentoPaiDaRecorrencia()->recorrencia_ate->toDateString());

        $this->gerarOcorrenciasAte(2027, 6);

        $this->assertSame(
            ['2026-11-10', '2026-12-10', '2027-01-10'],
            $this->listarDatasDasOcorrencias(),
        );
    }

    public function test_quantidade_de_ocorrencias_semanal_avanca_por_semana(): void
    {
        $this->criarLancamentoRecorrente([
            'frequencia_recorrencia' => FrequenciaRecorrencia::Semanal->value,
            'quantidade_ocorrencias' => 4,
        ])->assertSessionHasNoErrors();

        $this->assertSame('2026-12-01', $this->buscarLancamentoPaiDaRecorrencia()->recorrencia_ate->toDateString());
    }

    public function test_recorrencia_ate_o_mes_nao_gera_ocorrencias_depois_do_mes_final(): void
    {
        $this->criarLancamentoRecorrente(['recorrencia_ate' => '2027-02-28'])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('erro');

        $this->gerarOcorrenciasAte(2027, 6);

        $this->assertSame(
            ['2026-11-10', '2026-12-10', '2027-01-10', '2027-02-10'],
            $this->listarDatasDasOcorrencias(),
        );
    }

    public function test_nao_aceita_quantidade_e_mes_final_juntos(): void
    {
        $this->criarLancamentoRecorrente([
            'quantidade_ocorrencias' => 3,
            'recorrencia_ate' => '2027-02-28',
        ])->assertSessionHasErrors('quantidade_ocorrencias');

        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_quantidade_de_ocorrencias_precisa_ser_ao_menos_duas(): void
    {
        $this->criarLancamentoRecorrente(['quantidade_ocorrencias' => 1])
            ->assertSessionHasErrors('quantidade_ocorrencias');

        $this->assertDatabaseCount('lancamentos', 0);
    }

    public function test_recorrencia_sem_termino_continua_gerando_ocorrencias(): void
    {
        $this->criarLancamentoRecorrente()
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('erro');

        $this->assertNull($this->buscarLancamentoPaiDaRecorrencia()->recorrencia_ate);

        $this->gerarOcorrenciasAte(2027, 4);

        $this->assertCount(6, $this->listarDatasDasOcorrencias());
    }

    public function test_parcelado_divide_o_valor_total_e_ultima_parcela_absorve_a_diferenca(): void
    {
        $this->enviarLancamento([
            'descricao' => 'Notebook',
            'valor' => '100,00',
            'recorrente' => false,
            'total_parcelas' => 3,
        ])->assertSessionHasNoErrors()->assertSessionMissing('erro');

        $valoresDasParcelas = Lancamento::query()
            ->where('id_usuario', $this->usuario->id_usuario)
            ->orderBy('parcela_atual')
            ->pluck('valor')
            ->map(fn ($valor) => (float) $valor)
            ->all();

        $this->assertSame([33.33, 33.33, 33.34], $valoresDasParcelas);
    }

    private function criarLancamentoRecorrente(array $dadosExtras = []): TestResponse
    {
        return $this->enviarLancamento([
            'descricao' => 'Academia',
            'valor' => '120,00',
            'recorrente' => true,
            'frequencia_recorrencia' => FrequenciaRecorrencia::Mensal->value,
            'total_parcelas' => 1,
            ...$dadosExtras,
        ]);
    }

    private function enviarLancamento(array $dados): TestResponse
    {
        return $this->actingAs($this->usuario)
            ->withSession([EnsureAbsoluteSessionTimeout::SESSION_LOGIN_AT => now()->getTimestamp()])
            ->post(self::URL_LANCAMENTOS, [
                'data_vencimento' => self::DATA_PRIMEIRO_LANCAMENTO,
                'tipo' => TipoLancamento::Despesa->value,
                'forma_pagamento' => FormaPagamento::ContaBancaria->value,
                'id_conta_bancaria' => $this->conta->id_conta_bancaria,
                ...$dados,
            ]);
    }

    private function gerarOcorrenciasAte(int $ano, int $mes): void
    {
        app(RecorrenciaService::class)->gerarParaMes((int) $this->usuario->id_usuario, $ano, $mes);
    }

    private function buscarLancamentoPaiDaRecorrencia(): Lancamento
    {
        return Lancamento::query()
            ->where('id_usuario', $this->usuario->id_usuario)
            ->where('eh_recorrencia', SimNao::Sim)
            ->firstOrFail();
    }

    private function listarDatasDasOcorrencias(): array
    {
        return Lancamento::query()
            ->where('id_lancamento_pai', $this->buscarLancamentoPaiDaRecorrencia()->id_lancamento)
            ->orderBy('data_vencimento')
            ->get()
            ->map(fn (Lancamento $lancamento) => $lancamento->data_vencimento->toDateString())
            ->all();
    }

    private function criarContaBancaria(): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $this->usuario->id_usuario,
            'nome' => 'Banco Inter',
            'saldo_inicial' => 0,
            'tipo' => TipoContaBancaria::Corrente,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Sim,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }
}
