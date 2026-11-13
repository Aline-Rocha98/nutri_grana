<?php

namespace App\Services\PlanoFinanceiro;

use App\Enum\TipoCalculoItemPlano;
use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use Generator;
use Illuminate\Support\Collection;

class CalculadoraPlanoFinanceiro
{
    public function filtrarItensQueSeparamDoSaldo(Collection $itens): Collection
    {
        return $itens
            ->filter(fn (ItemPlanoFinanceiro $item) => $item->separaDoSaldo())
            ->values();
    }

    public function calcularValorDoItem(ItemPlanoFinanceiro $item, float $valorBase): float
    {
        return $item->tipo_calculo->calcularValorDoItem($valorBase, (float) $item->valor);
    }

    public function distribuirValoresSeparados(Collection $itens, float $valorBase): array
    {
        $valoresPorItem = [];

        foreach ($this->percorrerDistribuicaoSeparada($itens, $valorBase) as [$item, $valorSeparado]) {
            $valoresPorItem[(int) $item->id_item_plano] = $valorSeparado;
        }

        return $valoresPorItem;
    }

    public function somarValoresSeparados(Collection $itens, float $valorBase): float
    {
        $totalSeparado = 0.0;

        foreach ($this->percorrerDistribuicaoSeparada($itens, $valorBase) as [, $valorSeparado]) {
            $totalSeparado += $valorSeparado;
        }

        return round($totalSeparado, 2);
    }

    public function calcularCapacidadeFinanceira(float $valorRenda, Collection $itens): array
    {
        $valorRenda = round(max(0, $valorRenda), 2);
        $totalSeparado = $this->somarValoresSeparados($itens, $valorRenda);
        $capacidade = round($valorRenda - $totalSeparado, 2);
        $percentualSeparado = $valorRenda > 0
            ? round(($totalSeparado / $valorRenda) * 100, 1)
            : 0.0;

        return [
            'renda' => $valorRenda,
            'total_destinado' => $totalSeparado,
            'capacidade' => $capacidade,
            'percentual_destinado' => $percentualSeparado,
        ];
    }

    public function somaSeparadaUltrapassaRenda(Collection $itens, float $valorRenda): bool
    {
        $somaConfigurada = $this->filtrarItensQueSeparamDoSaldo($itens)->sum(
            fn (ItemPlanoFinanceiro $item) => $this->calcularValorConfiguradoSemLimite($item, $valorRenda),
        );

        return round($somaConfigurada, 2) > round($valorRenda, 2);
    }

    public function somarPercentualDosItens(Collection $itens): float
    {
        return round($itens
            ->filter(fn (ItemPlanoFinanceiro $item) => $item->ehPercentual())
            ->sum(fn (ItemPlanoFinanceiro $item) => (float) $item->valor), 2);
    }

    private function percorrerDistribuicaoSeparada(Collection $itens, float $valorBase): Generator
    {
        $saldoRestante = round(max(0, $valorBase), 2);

        foreach ($this->filtrarItensQueSeparamDoSaldo($itens) as $item) {
            $valorSeparado = round(min($this->calcularValorDoItem($item, $valorBase), $saldoRestante), 2);
            $saldoRestante = round($saldoRestante - $valorSeparado, 2);

            yield [$item, $valorSeparado];
        }
    }

    private function calcularValorConfiguradoSemLimite(ItemPlanoFinanceiro $item, float $valorRenda): float
    {
        return match ($item->tipo_calculo) {
            TipoCalculoItemPlano::Percentual => round($valorRenda * (float) $item->valor / 100, 2),
            TipoCalculoItemPlano::ValorFixo => round((float) $item->valor, 2),
        };
    }
}
