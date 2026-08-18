<?php

namespace Tests\Unit;

use App\Enum\FormaPagamento;
use App\Enum\ModalidadePagamentoOrcamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Services\Orcamento\MontadorCompromissosOrcamentoServico;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class MontadorCompromissosOrcamentoServicoTest extends TestCase
{
    public function test_distribuir_parcelas_fecha_centavos_na_ultima(): void
    {
        $montador = new MontadorCompromissosOrcamentoServico();

        $parcelas = $montador->distribuirParcelas(1000, 3);

        $this->assertSame([333.33, 333.33, 333.34], $parcelas);
        $this->assertSame(1000.0, round(array_sum($parcelas), 2));
    }

    public function test_monta_compromissos_conta_parcelada(): void
    {
        $montador = new MontadorCompromissosOrcamentoServico();

        $resultado = $montador->montar([
            'valor' => 900,
            'data_orcamento' => '2026-08-17',
            'modalidade_pagamento' => ModalidadePagamentoOrcamento::Parcelado->value,
            'forma_pagamento' => FormaPagamento::ContaBancaria->value,
            'total_parcelas' => 3,
        ], null, Carbon::parse('2026-08-17'));

        $this->assertCount(3, $resultado['compromissos']);
        $this->assertSame(300.0, $resultado['valor_parcela']);
        $this->assertFalse($resultado['consome_limite_cartao']);
        $this->assertSame('2026-10-17', $resultado['data_ultimo_compromisso']->toDateString());
    }

    public function test_monta_compromissos_cartao_usa_vencimento_da_fatura(): void
    {
        $montador = new MontadorCompromissosOrcamentoServico();
        $cartao = new CartaoCredito([
            'dia_vencimento' => 10,
            'dia_fechamento' => 1,
        ]);

        $resultado = $montador->montar([
            'valor' => 600,
            'data_orcamento' => '2026-08-17',
            'modalidade_pagamento' => ModalidadePagamentoOrcamento::AVista->value,
            'forma_pagamento' => FormaPagamento::CartaoCredito->value,
            'total_parcelas' => 1,
        ], $cartao, Carbon::parse('2026-08-17'));

        $this->assertTrue($resultado['consome_limite_cartao']);
        $this->assertSame(600.0, $resultado['valor_limite_cartao']);
        $this->assertSame('2026-08-10', $resultado['compromissos'][0]['data']->toDateString());
    }
}
