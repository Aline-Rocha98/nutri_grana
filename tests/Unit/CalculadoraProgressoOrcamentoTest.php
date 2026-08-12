<?php

namespace Tests\Unit;

use App\Services\Orcamento\CalculadoraProgressoOrcamento;
use PHPUnit\Framework\TestCase;

class CalculadoraProgressoOrcamentoTest extends TestCase
{
    public function test_calcula_progresso_abaixo_do_limite(): void
    {
        $calculadora = new CalculadoraProgressoOrcamento();

        $resumo = $calculadora->montarResumo(1000, 620);

        $this->assertSame(620.0, $resumo['gasto_mes']);
        $this->assertSame(380.0, $resumo['valor_restante']);
        $this->assertSame(0.0, $resumo['valor_excedente']);
        $this->assertSame(62.0, $resumo['percentual']);
        $this->assertSame(62.0, $resumo['percentual_barra']);
        $this->assertFalse($resumo['ultrapassado']);
    }

    public function test_calcula_progresso_igual_ao_limite(): void
    {
        $calculadora = new CalculadoraProgressoOrcamento();

        $resumo = $calculadora->montarResumo(1000, 1000);

        $this->assertSame(0.0, $resumo['valor_restante']);
        $this->assertSame(100.0, $resumo['percentual']);
        $this->assertFalse($resumo['ultrapassado']);
    }

    public function test_calcula_progresso_ultrapassado(): void
    {
        $calculadora = new CalculadoraProgressoOrcamento();

        $resumo = $calculadora->montarResumo(1000, 1250);

        $this->assertSame(0.0, $resumo['valor_restante']);
        $this->assertSame(250.0, $resumo['valor_excedente']);
        $this->assertSame(125.0, $resumo['percentual']);
        $this->assertSame(100.0, $resumo['percentual_barra']);
        $this->assertTrue($resumo['ultrapassado']);
    }
}
