<?php

namespace Tests\Unit;

use App\Enum\SituacaoRitmoObjetivo;
use App\Services\Objetivo\CalculadoraProgressoObjetivo;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CalculadoraProgressoObjetivoTest extends TestCase
{
    private CalculadoraProgressoObjetivo $calculadora;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculadora = new CalculadoraProgressoObjetivo;
    }

    public function test_calcula_percentual_e_valor_faltante(): void
    {
        $resumo = $this->calculadora->montarResumo(
            valorMeta: 1000.00,
            valorGuardado: 250.00,
            dataInicio: Carbon::parse('2026-01-01'),
            dataLimite: Carbon::parse('2026-12-31'),
            hoje: Carbon::parse('2026-01-01'),
        );

        $this->assertSame(25.0, $resumo['percentual_atual']);
        $this->assertSame(250.0, $resumo['valor_guardado']);
        $this->assertSame(750.0, $resumo['valor_faltante']);
    }

    public function test_marca_como_adiantado_quando_guardado_acima_do_esperado(): void
    {
        $resumo = $this->calculadora->montarResumo(
            valorMeta: 1000.00,
            valorGuardado: 800.00,
            dataInicio: Carbon::parse('2026-01-01'),
            dataLimite: Carbon::parse('2026-12-31'),
            hoje: Carbon::parse('2026-04-01'),
        );

        $this->assertSame(SituacaoRitmoObjetivo::Adiantado->value, $resumo['situacao_ritmo']);
    }

    public function test_marca_como_atrasado_quando_guardado_abaixo_do_esperado(): void
    {
        $resumo = $this->calculadora->montarResumo(
            valorMeta: 1000.00,
            valorGuardado: 50.00,
            dataInicio: Carbon::parse('2026-01-01'),
            dataLimite: Carbon::parse('2026-12-31'),
            hoje: Carbon::parse('2026-07-01'),
        );

        $this->assertSame(SituacaoRitmoObjetivo::Atrasado->value, $resumo['situacao_ritmo']);
    }

    public function test_sugere_deposito_mensal_com_base_no_faltante(): void
    {
        $resumo = $this->calculadora->montarResumo(
            valorMeta: 1200.00,
            valorGuardado: 0.00,
            dataInicio: Carbon::parse('2026-01-01'),
            dataLimite: Carbon::parse('2026-07-01'),
            hoje: Carbon::parse('2026-01-01'),
        );

        $this->assertSame(200.0, $resumo['deposito_mensal_sugerido']);
        $this->assertSame(6, $resumo['meses_restantes']);
    }

    public function test_marca_como_concluido_ao_atingir_a_meta(): void
    {
        $resumo = $this->calculadora->montarResumo(
            valorMeta: 500.00,
            valorGuardado: 500.00,
            dataInicio: Carbon::parse('2026-01-01'),
            dataLimite: Carbon::parse('2026-12-31'),
            hoje: Carbon::parse('2026-06-01'),
        );

        $this->assertSame(SituacaoRitmoObjetivo::Concluido->value, $resumo['situacao_ritmo']);
        $this->assertSame(0.0, $resumo['valor_faltante']);
        $this->assertSame(100.0, $resumo['percentual_atual']);
    }
}
