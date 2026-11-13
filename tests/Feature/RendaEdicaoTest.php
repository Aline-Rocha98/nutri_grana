<?php

namespace Tests\Feature;

use App\Enum\FrequenciaRecorrencia;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoContaBancaria;
use App\Http\Middleware\EnsureAbsoluteSessionTimeout;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Renda\Renda;
use App\Models\Usuario\Usuario;
use App\Services\Renda\RendaGeracaoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RendaEdicaoTest extends TestCase
{
    use RefreshDatabase;

    private const URL_RENDAS = '/rendas';

    public function test_trocar_conta_e_dia_de_renda_com_previsto_vencido_nao_duplica_lancamento(): void
    {
        $usuario = Usuario::factory()->create();
        $nubank = $this->criarConta($usuario, 'Nubank');
        $inter = $this->criarConta($usuario, 'Banco Inter');

        $this->travelTo(Carbon::create(2026, 11, 3));
        $renda = $this->criarRendaPelaTela($usuario, $nubank, 1);

        $this->travelTo(Carbon::create(2026, 11, 5));
        $this->editarRendaPelaTela($usuario, $renda, $inter, 5);

        $lancamentos = $this->listarOcorrenciasAtivas($renda);

        $this->assertCount(1, $lancamentos);
        $this->assertSame('2026-11-05', $lancamentos->first()->data_vencimento->toDateString());
        $this->assertSame((int) $inter->id_conta_bancaria, (int) $lancamentos->first()->id_conta_bancaria);
    }

    public function test_trocar_somente_a_conta_atualiza_previsto_vencido(): void
    {
        $usuario = Usuario::factory()->create();
        $nubank = $this->criarConta($usuario, 'Nubank');
        $inter = $this->criarConta($usuario, 'Banco Inter');

        $this->travelTo(Carbon::create(2026, 11, 3));
        $renda = $this->criarRendaPelaTela($usuario, $nubank, 1);

        $this->travelTo(Carbon::create(2026, 11, 5));
        $this->editarRendaPelaTela($usuario, $renda, $inter, 1);

        $lancamentos = $this->listarOcorrenciasAtivas($renda);

        $this->assertCount(1, $lancamentos);
        $this->assertSame('2026-11-01', $lancamentos->first()->data_vencimento->toDateString());
        $this->assertSame((int) $inter->id_conta_bancaria, (int) $lancamentos->first()->id_conta_bancaria);
    }

    public function test_mudar_dia_de_renda_ja_recebida_nao_cria_outro_lancamento_no_mes(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 'Nubank');

        $this->travelTo(Carbon::create(2026, 11, 1));
        $renda = $this->criarRendaPelaTela($usuario, $conta, 1);
        app(RendaGeracaoService::class)->gerarOcorrenciasDoMes($renda->fresh(), 2026, 12);
        $this->listarOcorrenciasAtivas($renda)->first()->update(['situacao' => SituacaoLancamento::Recebido]);

        $this->travelTo(Carbon::create(2026, 11, 5));
        $this->editarRendaPelaTela($usuario, $renda, $conta, 10);

        $lancamentos = $this->listarOcorrenciasAtivas($renda);

        $this->assertCount(2, $lancamentos);
        $this->assertSame('2026-11-01', $lancamentos[0]->data_vencimento->toDateString());
        $this->assertSame(SituacaoLancamento::Recebido, $lancamentos[0]->situacao);
        $this->assertSame('2026-12-10', $lancamentos[1]->data_vencimento->toDateString());
        $this->assertSame(SituacaoLancamento::Previsto, $lancamentos[1]->situacao);
    }

    public function test_geracao_do_mes_apos_edicao_nao_duplica_lancamento(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 'Nubank');

        $this->travelTo(Carbon::create(2026, 11, 1));
        $renda = $this->criarRendaPelaTela($usuario, $conta, 1);
        $this->listarOcorrenciasAtivas($renda)->first()->update(['situacao' => SituacaoLancamento::Recebido]);

        $this->travelTo(Carbon::create(2026, 11, 5));
        $this->editarRendaPelaTela($usuario, $renda, $conta, 20);
        app(RendaGeracaoService::class)->gerarParaMes((int) $usuario->id_usuario, 2026, 11);

        $this->assertCount(1, $this->listarOcorrenciasAtivas($renda));
    }

    public function test_mudar_frequencia_para_anual_remove_previstos_que_sobraram(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 'Nubank');

        $this->travelTo(Carbon::create(2026, 11, 3));
        $renda = $this->criarRendaPelaTela($usuario, $conta, 5);
        app(RendaGeracaoService::class)->gerarOcorrenciasDoMes($renda->fresh(), 2026, 12);

        $this->editarRendaPelaTela($usuario, $renda, $conta, 5, FrequenciaRecorrencia::Anual);

        $lancamentos = $this->listarOcorrenciasAtivas($renda);

        $this->assertCount(1, $lancamentos);
        $this->assertSame('2026-11-05', $lancamentos->first()->data_vencimento->toDateString());
    }

    public function test_mudar_frequencia_para_semanal_gera_uma_ocorrencia_por_semana_sem_duplicar(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario, 'Nubank');

        $this->travelTo(Carbon::create(2026, 11, 3));
        $renda = $this->criarRendaPelaTela($usuario, $conta, 5);

        $this->editarRendaPelaTela($usuario, $renda, $conta, 5, FrequenciaRecorrencia::Semanal);

        $datas = $this->listarOcorrenciasAtivas($renda)
            ->map(fn (Lancamento $lancamento) => $lancamento->data_vencimento->toDateString())
            ->all();

        $this->assertSame(['2026-11-05', '2026-11-12', '2026-11-19', '2026-11-26'], $datas);
    }

    private function criarRendaPelaTela(Usuario $usuario, ContaBancaria $conta, int $diaEsperado): Renda
    {
        $this->actingAs($usuario)
            ->withSession([EnsureAbsoluteSessionTimeout::SESSION_LOGIN_AT => now()->getTimestamp()])
            ->post(self::URL_RENDAS, $this->montarDadosRenda($conta, $diaEsperado, FrequenciaRecorrencia::Mensal))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('erro')
            ->assertRedirect(self::URL_RENDAS);

        return Renda::query()->where('id_usuario', $usuario->id_usuario)->latest('id_renda')->firstOrFail();
    }

    private function editarRendaPelaTela(
        Usuario $usuario,
        Renda $renda,
        ContaBancaria $conta,
        int $diaEsperado,
        FrequenciaRecorrencia $frequencia = FrequenciaRecorrencia::Mensal,
    ): void {
        $this->actingAs($usuario)
            ->withSession([EnsureAbsoluteSessionTimeout::SESSION_LOGIN_AT => now()->getTimestamp()])
            ->put(self::URL_RENDAS.'/'.$renda->getRouteKey(), $this->montarDadosRenda($conta, $diaEsperado, $frequencia))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('erro')
            ->assertRedirect(self::URL_RENDAS);
    }

    private function montarDadosRenda(ContaBancaria $conta, int $diaEsperado, FrequenciaRecorrencia $frequencia): array
    {
        return [
            'descricao' => 'Salário',
            'valor_esperado' => '4.000,00',
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'frequencia' => $frequencia->value,
            'dia_esperado' => $diaEsperado,
        ];
    }

    private function listarOcorrenciasAtivas(Renda $renda): Collection
    {
        return Lancamento::query()
            ->where('id_renda', $renda->id_renda)
            ->where('situacao', '!=', SituacaoLancamento::Cancelado)
            ->orderBy('data_vencimento')
            ->get();
    }

    private function criarConta(Usuario $usuario, string $nome): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => $nome,
            'saldo_inicial' => 0,
            'tipo' => TipoContaBancaria::Corrente,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Nao,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }
}
