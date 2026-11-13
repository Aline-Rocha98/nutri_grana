<?php

namespace Tests\Unit;

use App\Services\Home\MontadorDicaFinanceira;
use PHPUnit\Framework\TestCase;

class MontadorDicaFinanceiraTest extends TestCase
{
    private MontadorDicaFinanceira $montador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->montador = new MontadorDicaFinanceira;
    }

    public function test_contas_fixas_saudaveis_geram_tom_positivo_com_destaques(): void
    {
        $dica = $this->montador->montarDicaFinanceira(21050.00, 10.0);

        $this->assertSame(MontadorDicaFinanceira::TOM_POSITIVO, $dica['tom']);
        $this->assertSame(['R$ 21.050,00', '10%'], $this->textosDestacados($dica));
        $this->assertStringContainsString('Excelente controle', $this->textoCompleto($dica));
    }

    public function test_contas_fixas_entre_trinta_e_cinquenta_por_cento_geram_atencao(): void
    {
        $dica = $this->montador->montarDicaFinanceira(500.00, 42.5);

        $this->assertSame(MontadorDicaFinanceira::TOM_ATENCAO, $dica['tom']);
        $this->assertContains('42,5%', $this->textosDestacados($dica));
        $this->assertStringContainsString('Fique de olho', $this->textoCompleto($dica));
    }

    public function test_contas_fixas_acima_de_cinquenta_por_cento_geram_alerta(): void
    {
        $dica = $this->montador->montarDicaFinanceira(500.00, 65.0);

        $this->assertSame(MontadorDicaFinanceira::TOM_ALERTA, $dica['tom']);
        $this->assertContains('65%', $this->textosDestacados($dica));
        $this->assertStringContainsString('Vale revisar', $this->textoCompleto($dica));
    }

    public function test_sem_saldo_guardado_sugere_aplicar_plano_financeiro(): void
    {
        $dica = $this->montador->montarDicaFinanceira(0.0, 20.0);

        $this->assertSame(MontadorDicaFinanceira::TOM_ATENCAO, $dica['tom']);
        $this->assertStringContainsString('Aplique um plano financeiro', $this->textoCompleto($dica));
    }

    public function test_sem_receitas_no_mes_orienta_cadastro(): void
    {
        $dica = $this->montador->montarDicaFinanceira(1000.00, null);

        $this->assertSame(MontadorDicaFinanceira::TOM_POSITIVO, $dica['tom']);
        $this->assertStringContainsString('Cadastre suas receitas', $this->textoCompleto($dica));
    }

    private function textosDestacados(array $dica): array
    {
        return array_values(array_map(
            fn (array $parte) => $parte['texto'],
            array_filter($dica['partes'], fn (array $parte) => $parte['destaque']),
        ));
    }

    private function textoCompleto(array $dica): string
    {
        return implode('', array_column($dica['partes'], 'texto'));
    }
}
