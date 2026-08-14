<?php

namespace Tests\Unit;

use App\Services\Orcamento\CalculadoraViabilidadeOrcamentoServico;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CalculadoraViabilidadeOrcamentoServicoTest extends TestCase
{
    public function test_indica_pagamento_integral_quando_ha_disponivel(): void
    {
        $calculadora = new CalculadoraViabilidadeOrcamentoServico();

        $projecaoSem = $this->projecaoBase(5000, 700, 4800);
        $projecaoCom = $this->projecaoBase(5000, 700, -1200);

        $resumo = $calculadora->montarResumo(
            600,
            $projecaoSem,
            $projecaoCom,
            Carbon::parse('2026-12-31'),
            Carbon::parse('2026-08-14'),
        );

        $this->assertTrue($resumo['pago_integralmente_agora']);
        $this->assertSame(1, $resumo['meses_ate_pagar']);
        $this->assertTrue($resumo['compromete_fluxo']);
        $this->assertStringContainsString('já consegue pagar', $resumo['mensagem_principal']);
        $this->assertNotNull($resumo['mensagem_alerta']);
    }

    public function test_estima_meses_ate_pagar(): void
    {
        $calculadora = new CalculadoraViabilidadeOrcamentoServico();

        $projecaoSem = [
            'saldo_atual_contas' => 1000.0,
            'receitas_previstas' => 3000.0,
            'despesas_previstas' => 2500.0,
            'saldo_disponivel_planejamento' => 1500.0,
            'meses' => [
                [
                    'ano' => 2026,
                    'mes' => 8,
                    'rotulo' => 'Agosto/2026',
                    'receitas' => 1000.0,
                    'despesas' => 500.0,
                    'saldo_projetado' => 1500.0,
                ],
                [
                    'ano' => 2026,
                    'mes' => 9,
                    'rotulo' => 'Setembro/2026',
                    'receitas' => 1000.0,
                    'despesas' => 500.0,
                    'saldo_projetado' => 2000.0,
                ],
                [
                    'ano' => 2026,
                    'mes' => 10,
                    'rotulo' => 'Outubro/2026',
                    'receitas' => 1000.0,
                    'despesas' => 500.0,
                    'saldo_projetado' => 2500.0,
                ],
            ],
            'saldo_projetado_final' => 2500.0,
            'horizonte_ate' => '2026-10-31',
        ];

        $projecaoCom = $projecaoSem;
        $projecaoCom['saldo_projetado_final'] = -3500.0;
        $projecaoCom['meses'][2]['saldo_projetado'] = -3500.0;

        $resumo = $calculadora->montarResumo(
            2200,
            $projecaoSem,
            $projecaoCom,
            Carbon::parse('2026-10-31'),
            Carbon::parse('2026-08-01'),
        );

        $this->assertFalse($resumo['pago_integralmente_agora']);
        $this->assertSame(3, $resumo['meses_ate_pagar']);
        $this->assertStringContainsString('3 meses', $resumo['mensagem_principal']);
    }

    /**
     * @return array<string, mixed>
     */
    private function projecaoBase(float $saldoAtual, float $disponivel, float $saldoFinal): array
    {
        return [
            'saldo_atual_contas' => $saldoAtual,
            'receitas_previstas' => 1000.0,
            'despesas_previstas' => $saldoAtual + 1000 - $disponivel,
            'saldo_disponivel_planejamento' => $disponivel,
            'meses' => [
                [
                    'ano' => 2026,
                    'mes' => 12,
                    'rotulo' => 'Dezembro/2026',
                    'receitas' => 1000.0,
                    'despesas' => 500.0,
                    'saldo_projetado' => $saldoFinal,
                ],
            ],
            'saldo_projetado_final' => $saldoFinal,
            'horizonte_ate' => '2026-12-31',
        ];
    }
}
