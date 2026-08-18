<?php

namespace App\Services\Orcamento;

use App\Enum\FormaPagamento;
use App\Enum\ModalidadePagamentoOrcamento;
use App\Models\CartaoCredito\CartaoCredito;
use Carbon\Carbon;

class MontadorCompromissosOrcamentoServico
{
    /**
     * @return array{
     *     compromissos: list<array{valor: float, data: Carbon, parcela: int}>,
     *     valor_parcela: float,
     *     total_parcelas: int,
     *     data_ultimo_compromisso: Carbon,
     *     consome_limite_cartao: bool,
     *     valor_limite_cartao: float
     * }
     */
    public function montar(array $dados, ?CartaoCredito $cartao = null, ?Carbon $referencia = null): array
    {
        $referencia = ($referencia ?? Carbon::today())->copy()->startOfDay();
        $valor = round(max(0, (float) $dados['valor']), 2);
        $modalidade = ModalidadePagamentoOrcamento::from(
            $dados['modalidade_pagamento'] ?? ModalidadePagamentoOrcamento::AVista->value
        );
        $forma = FormaPagamento::from(
            $dados['forma_pagamento'] ?? FormaPagamento::ContaBancaria->value
        );
        $totalParcelas = $modalidade === ModalidadePagamentoOrcamento::Parcelado
            ? max(2, min(48, (int) ($dados['total_parcelas'] ?? 2)))
            : 1;

        $inicio = Carbon::parse($dados['data_orcamento'] ?? $referencia)->startOfDay();
        if ($inicio->lt($referencia)) {
            $inicio = $referencia->copy();
        }

        $valoresParcelas = $this->distribuirParcelas($valor, $totalParcelas);
        $compromissos = [];

        for ($i = 0; $i < $totalParcelas; $i++) {
            $dataImpacto = $forma === FormaPagamento::CartaoCredito
                ? $this->dataVencimentoFatura($cartao, $inicio->copy()->addMonthsNoOverflow($i))
                : $inicio->copy()->addMonthsNoOverflow($i);

            $compromissos[] = [
                'valor' => $valoresParcelas[$i],
                'data' => $dataImpacto,
                'parcela' => $i + 1,
            ];
        }

        $ultimo = $compromissos[array_key_last($compromissos)]['data'];

        return [
            'compromissos' => $compromissos,
            'valor_parcela' => $valoresParcelas[0] ?? $valor,
            'total_parcelas' => $totalParcelas,
            'data_ultimo_compromisso' => $ultimo,
            'consome_limite_cartao' => $forma === FormaPagamento::CartaoCredito,
            'valor_limite_cartao' => $forma === FormaPagamento::CartaoCredito ? $valor : 0.0,
        ];
    }

    /**
     * @return list<float>
     */
    public function distribuirParcelas(float $valorTotal, int $parcelas): array
    {
        if ($parcelas <= 1) {
            return [round($valorTotal, 2)];
        }

        $base = round($valorTotal / $parcelas, 2);
        $valores = array_fill(0, $parcelas, $base);
        $soma = round($base * $parcelas, 2);
        $diferenca = round($valorTotal - $soma, 2);
        $valores[$parcelas - 1] = round($valores[$parcelas - 1] + $diferenca, 2);

        return $valores;
    }

    private function dataVencimentoFatura(?CartaoCredito $cartao, Carbon $dataCompra): Carbon
    {
        $ano = (int) $dataCompra->year;
        $mes = (int) $dataCompra->month;
        $diaVencimento = (int) ($cartao?->dia_vencimento ?: $dataCompra->day);

        return $this->dataComDia($ano, $mes, $diaVencimento);
    }

    private function dataComDia(int $ano, int $mes, int $dia): Carbon
    {
        $ultimoDia = (int) Carbon::create($ano, $mes, 1)->endOfMonth()->day;
        $diaAjustado = min(max($dia, 1), $ultimoDia);

        return Carbon::create($ano, $mes, $diaAjustado)->startOfDay();
    }
}
