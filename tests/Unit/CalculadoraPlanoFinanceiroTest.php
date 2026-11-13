<?php

namespace Tests\Unit;

use App\Enum\SimNao;
use App\Enum\TipoCalculoItemPlano;
use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use App\Services\PlanoFinanceiro\CalculadoraPlanoFinanceiro;
use Tests\TestCase;

class CalculadoraPlanoFinanceiroTest extends TestCase
{
    private CalculadoraPlanoFinanceiro $calculadora;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculadora = new CalculadoraPlanoFinanceiro;
    }

    public function test_calcula_valor_do_item_percentual(): void
    {
        $item = $this->criarItemPlano(1, TipoCalculoItemPlano::Percentual, 10);

        $this->assertSame(400.0, $this->calculadora->calcularValorDoItem($item, 4000));
    }

    public function test_calcula_valor_do_item_fixo_limitado_ao_valor_base(): void
    {
        $item = $this->criarItemPlano(1, TipoCalculoItemPlano::ValorFixo, 500);

        $this->assertSame(500.0, $this->calculadora->calcularValorDoItem($item, 4000));
        $this->assertSame(300.0, $this->calculadora->calcularValorDoItem($item, 300));
    }

    public function test_percentual_e_recalculado_sobre_valor_recebido_menor(): void
    {
        $item = $this->criarItemPlano(1, TipoCalculoItemPlano::Percentual, 10);

        $this->assertSame(350.0, $this->calculadora->calcularValorDoItem($item, 3500));
    }

    public function test_itens_orientativos_nao_entram_na_distribuicao(): void
    {
        $itens = collect([
            $this->criarItemPlano(1, TipoCalculoItemPlano::Percentual, 50, separarDoSaldo: false),
            $this->criarItemPlano(2, TipoCalculoItemPlano::Percentual, 30),
            $this->criarItemPlano(3, TipoCalculoItemPlano::Percentual, 20, separarDoSaldo: false),
        ]);

        $valores = $this->calculadora->distribuirValoresSeparados($itens, 5000);

        $this->assertSame([2 => 1500.0], $valores);
        $this->assertSame(1500.0, $this->calculadora->somarValoresSeparados($itens, 5000));
    }

    public function test_distribuicao_nunca_ultrapassa_valor_base(): void
    {
        $itens = collect([
            $this->criarItemPlano(1, TipoCalculoItemPlano::ValorFixo, 800),
            $this->criarItemPlano(2, TipoCalculoItemPlano::Percentual, 50),
        ]);

        $valores = $this->calculadora->distribuirValoresSeparados($itens, 1000);

        $this->assertSame(800.0, $valores[1]);
        $this->assertSame(200.0, $valores[2]);
        $this->assertSame(1000.0, $this->calculadora->somarValoresSeparados($itens, 1000));
    }

    public function test_calcula_capacidade_financeira_considerando_apenas_itens_que_separam(): void
    {
        $itens = collect([
            $this->criarItemPlano(1, TipoCalculoItemPlano::Percentual, 50, separarDoSaldo: false),
            $this->criarItemPlano(2, TipoCalculoItemPlano::Percentual, 10),
            $this->criarItemPlano(3, TipoCalculoItemPlano::ValorFixo, 200),
        ]);

        $capacidade = $this->calculadora->calcularCapacidadeFinanceira(4000, $itens);

        $this->assertSame(4000.0, $capacidade['renda']);
        $this->assertSame(600.0, $capacidade['total_destinado']);
        $this->assertSame(3400.0, $capacidade['capacidade']);
        $this->assertSame(15.0, $capacidade['percentual_destinado']);
    }

    public function test_capacidade_sem_itens_e_igual_a_renda(): void
    {
        $capacidade = $this->calculadora->calcularCapacidadeFinanceira(4000, collect());

        $this->assertSame(0.0, $capacidade['total_destinado']);
        $this->assertSame(4000.0, $capacidade['capacidade']);
        $this->assertSame(0.0, $capacidade['percentual_destinado']);
    }

    public function test_plano_somente_orientativo_mantem_capacidade_igual_a_renda(): void
    {
        $itens = collect([
            $this->criarItemPlano(1, TipoCalculoItemPlano::Percentual, 50, separarDoSaldo: false),
            $this->criarItemPlano(2, TipoCalculoItemPlano::Percentual, 50, separarDoSaldo: false),
        ]);

        $capacidade = $this->calculadora->calcularCapacidadeFinanceira(3000, $itens);

        $this->assertSame(0.0, $capacidade['total_destinado']);
        $this->assertSame(3000.0, $capacidade['capacidade']);
    }

    public function test_identifica_soma_separada_acima_da_renda(): void
    {
        $dentroDoLimite = collect([
            $this->criarItemPlano(1, TipoCalculoItemPlano::Percentual, 50),
            $this->criarItemPlano(2, TipoCalculoItemPlano::ValorFixo, 2000),
        ]);
        $acimaDoLimite = collect([
            $this->criarItemPlano(1, TipoCalculoItemPlano::Percentual, 50),
            $this->criarItemPlano(2, TipoCalculoItemPlano::ValorFixo, 2000.01),
        ]);

        $this->assertFalse($this->calculadora->somaSeparadaUltrapassaRenda($dentroDoLimite, 4000));
        $this->assertTrue($this->calculadora->somaSeparadaUltrapassaRenda($acimaDoLimite, 4000));
    }

    public function test_item_orientativo_nao_conta_para_o_limite_da_renda(): void
    {
        $itens = collect([
            $this->criarItemPlano(1, TipoCalculoItemPlano::ValorFixo, 5000, separarDoSaldo: false),
            $this->criarItemPlano(2, TipoCalculoItemPlano::Percentual, 10),
        ]);

        $this->assertFalse($this->calculadora->somaSeparadaUltrapassaRenda($itens, 4000));
    }

    public function test_soma_percentual_considera_todos_os_itens_percentuais(): void
    {
        $itens = collect([
            $this->criarItemPlano(1, TipoCalculoItemPlano::Percentual, 50, separarDoSaldo: false),
            $this->criarItemPlano(2, TipoCalculoItemPlano::Percentual, 30),
            $this->criarItemPlano(3, TipoCalculoItemPlano::Percentual, 20.5, separarDoSaldo: false),
            $this->criarItemPlano(4, TipoCalculoItemPlano::ValorFixo, 300),
        ]);

        $this->assertSame(100.5, $this->calculadora->somarPercentualDosItens($itens));
    }

    private function criarItemPlano(
        int $idItem,
        TipoCalculoItemPlano $tipoCalculo,
        float $valor,
        bool $separarDoSaldo = true,
    ): ItemPlanoFinanceiro {
        $item = new ItemPlanoFinanceiro([
            'nome' => 'Item '.$idItem,
            'tipo_calculo' => $tipoCalculo,
            'valor' => $valor,
            'separar_do_saldo' => $separarDoSaldo ? SimNao::Sim : SimNao::Nao,
        ]);
        $item->setAttribute('id_item_plano', $idItem);

        return $item;
    }
}
