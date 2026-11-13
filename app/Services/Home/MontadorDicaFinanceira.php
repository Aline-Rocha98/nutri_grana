<?php

namespace App\Services\Home;

class MontadorDicaFinanceira
{
    public const LIMITE_CONTAS_FIXAS_SAUDAVEL = 30.0;

    public const LIMITE_CONTAS_FIXAS_ATENCAO = 50.0;

    public const TOM_POSITIVO = 'positivo';

    public const TOM_ATENCAO = 'atencao';

    public const TOM_ALERTA = 'alerta';

    public function montarDicaFinanceira(float $totalGuardado, ?float $percentualContasFixas): array
    {
        $partes = [
            ...$this->montarPartesDoGuardado($totalGuardado),
            ...$this->montarPartesDasContasFixas($percentualContasFixas),
        ];

        return [
            'tom' => $this->definirTom($totalGuardado, $percentualContasFixas),
            'partes' => $partes,
        ];
    }

    private function montarPartesDoGuardado(float $totalGuardado): array
    {
        if ($totalGuardado <= 0) {
            return [
                $this->parte('Você ainda não tem saldo guardado. Aplique um plano financeiro na sua renda para separar uma reserva automaticamente. '),
            ];
        }

        return [
            $this->parte('Você já guardou '),
            $this->parte('R$ ' . number_format($totalGuardado, 2, ',', '.'), true),
            $this->parte(' em reservas pelo plano financeiro das rendas. '),
        ];
    }

    private function montarPartesDasContasFixas(?float $percentualContasFixas): array
    {
        if ($percentualContasFixas === null) {
            return [
                $this->parte('Cadastre suas receitas do mês para acompanhar o peso das contas fixas.'),
            ];
        }

        $percentual = $this->formatarPercentual($percentualContasFixas);

        if ($percentualContasFixas <= self::LIMITE_CONTAS_FIXAS_SAUDAVEL) {
            return [
                $this->parte('Suas contas fixas deste mês representam apenas '),
                $this->parte($percentual, true),
                $this->parte(' da sua receita mensal. Excelente controle de gastos!'),
            ];
        }

        if ($percentualContasFixas <= self::LIMITE_CONTAS_FIXAS_ATENCAO) {
            return [
                $this->parte('Suas contas fixas deste mês representam '),
                $this->parte($percentual, true),
                $this->parte(' da sua receita mensal. Fique de olho para não comprometer sua margem.'),
            ];
        }

        return [
            $this->parte('Atenção: suas contas fixas deste mês já consomem '),
            $this->parte($percentual, true),
            $this->parte(' da sua receita mensal. Vale revisar assinaturas e despesas recorrentes.'),
        ];
    }

    private function definirTom(float $totalGuardado, ?float $percentualContasFixas): string
    {
        if ($percentualContasFixas !== null && $percentualContasFixas > self::LIMITE_CONTAS_FIXAS_ATENCAO) {
            return self::TOM_ALERTA;
        }

        if ($totalGuardado <= 0
            || ($percentualContasFixas !== null && $percentualContasFixas > self::LIMITE_CONTAS_FIXAS_SAUDAVEL)
        ) {
            return self::TOM_ATENCAO;
        }

        return self::TOM_POSITIVO;
    }

    private function formatarPercentual(float $percentual): string
    {
        $formatado = number_format($percentual, 1, ',', '.');

        return (str_ends_with($formatado, ',0') ? substr($formatado, 0, -2) : $formatado) . '%';
    }

    private function parte(string $texto, bool $destaque = false): array
    {
        return [
            'texto' => $texto,
            'destaque' => $destaque,
        ];
    }
}
