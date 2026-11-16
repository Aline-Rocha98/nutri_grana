<?php

namespace Tests\Unit;

use App\Support\Data\ValorMonetario;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValorMonetarioTest extends TestCase
{
    #[DataProvider('valoresParaConversaoEmCentavos')]
    public function test_converte_valores_para_centavos_sem_perder_precisao(string|int|float|null $valor, int $centavosEsperados): void
    {
        $this->assertSame($centavosEsperados, ValorMonetario::converterParaCentavos($valor));
    }

    public static function valoresParaConversaoEmCentavos(): array
    {
        return [
            'decimal com dois digitos' => ['1234.56', 123456],
            'decimal com um digito' => ['10.5', 1050],
            'sem decimais' => ['99', 9900],
            'inteiro' => [7, 700],
            'float com erro binario' => [0.1 + 0.2, 30],
            'negativo' => ['-15.75', -1575],
            'nulo' => [null, 0],
            'vazio' => ['', 0],
        ];
    }

    public function test_soma_em_centavos_nao_acumula_erro_de_ponto_flutuante(): void
    {
        $totalEmCentavos = array_sum(array_map(
            fn (string $valor) => ValorMonetario::converterParaCentavos($valor),
            array_fill(0, 10, '0.10')
        ));

        $this->assertSame(100, $totalEmCentavos);
        $this->assertSame('1.00', ValorMonetario::converterCentavosParaDecimal($totalEmCentavos));
    }

    public function test_converte_centavos_para_decimal(): void
    {
        $this->assertSame('1234.56', ValorMonetario::converterCentavosParaDecimal(123456));
        $this->assertSame('0.05', ValorMonetario::converterCentavosParaDecimal(5));
        $this->assertSame('-10.00', ValorMonetario::converterCentavosParaDecimal(-1000));
    }

    public function test_formata_centavos_em_reais(): void
    {
        $this->assertSame('1.234.567,89', ValorMonetario::formatarCentavosEmReais(123456789));
        $this->assertSame('0,30', ValorMonetario::formatarCentavosEmReais(30));
        $this->assertSame('-1,50', ValorMonetario::formatarCentavosEmReais(-150));
    }
}
