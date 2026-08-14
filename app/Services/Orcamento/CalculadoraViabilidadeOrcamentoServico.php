<?php

namespace App\Services\Orcamento;

use Carbon\Carbon;

class CalculadoraViabilidadeOrcamentoServico
{
    /**
     * @param  array{
     *     saldo_atual_contas: float,
     *     receitas_previstas: float,
     *     despesas_previstas: float,
     *     saldo_disponivel_planejamento: float,
     *     meses: list<array{ano: int, mes: int, rotulo: string, receitas: float, despesas: float, saldo_projetado: float}>,
     *     saldo_projetado_final: float,
     *     horizonte_ate: string
     * }  $projecaoSem
     * @param  array{
     *     saldo_atual_contas: float,
     *     receitas_previstas: float,
     *     despesas_previstas: float,
     *     saldo_disponivel_planejamento: float,
     *     meses: list<array{ano: int, mes: int, rotulo: string, receitas: float, despesas: float, saldo_projetado: float}>,
     *     saldo_projetado_final: float,
     *     horizonte_ate: string
     * }  $projecaoCom
     * @return array<string, mixed>
     */
    public function montarResumo(
        float $valorOrcamento,
        array $projecaoSem,
        array $projecaoCom,
        Carbon $dataValidade,
        ?Carbon $hoje = null,
    ): array {
        $hoje = ($hoje ?? Carbon::today())->copy()->startOfDay();
        $valorOrcamento = round(max(0, $valorOrcamento), 2);
        $disponivel = (float) $projecaoSem['saldo_disponivel_planejamento'];
        $saldoAtual = (float) $projecaoSem['saldo_atual_contas'];
        $saldoMesAtual = (float) (($projecaoSem['meses'][0]['saldo_projetado'] ?? $disponivel));

        $mesesAtePagar = $this->estimarMesesAtePagar($valorOrcamento, $projecaoSem['meses']);
        $comprometeFluxo = ((float) $projecaoCom['saldo_projetado_final']) < 0
            || $this->algumMesNegativo($projecaoCom['meses']);

        $pagoIntegralmenteAgora = $saldoMesAtual >= $valorOrcamento && $valorOrcamento > 0;
        $expirado = $dataValidade->copy()->startOfDay()->lt($hoje);

        $mesFinalSem = $this->ultimoMes($projecaoSem['meses']);
        $mesFinalCom = $this->ultimoMes($projecaoCom['meses']);

        return [
            'saldo_atual_contas' => $saldoAtual,
            'saldo_atual_contas_formatado' => $this->moeda($saldoAtual),
            'saldo_disponivel_planejamento' => $disponivel,
            'saldo_disponivel_planejamento_formatado' => $this->moeda($disponivel),
            'receitas_previstas' => (float) $projecaoSem['receitas_previstas'],
            'despesas_previstas' => (float) $projecaoSem['despesas_previstas'],
            'pago_integralmente_agora' => $pagoIntegralmenteAgora,
            'meses_ate_pagar' => $mesesAtePagar,
            'compromete_fluxo' => $comprometeFluxo,
            'expirado' => $expirado,
            'mensagem_principal' => $this->mensagemPrincipal(
                $valorOrcamento,
                $saldoMesAtual,
                $pagoIntegralmenteAgora,
                $mesesAtePagar,
                $expirado,
            ),
            'mensagem_disponivel' => $this->mensagemDisponivel($saldoAtual, $disponivel),
            'mensagem_alerta' => $comprometeFluxo
                ? 'Esse orçamento comprometeria seu fluxo de caixa futuro.'
                : null,
            'comparativo' => [
                'mes_rotulo' => $mesFinalSem['rotulo'] ?? null,
                'saldo_sem_orcamento' => (float) ($mesFinalSem['saldo_projetado'] ?? 0),
                'saldo_sem_orcamento_formatado' => $this->moeda((float) ($mesFinalSem['saldo_projetado'] ?? 0)),
                'saldo_com_orcamento' => (float) ($mesFinalCom['saldo_projetado'] ?? 0),
                'saldo_com_orcamento_formatado' => $this->moeda((float) ($mesFinalCom['saldo_projetado'] ?? 0)),
            ],
            'projecao_meses_sem' => $projecaoSem['meses'],
            'projecao_meses_com' => $projecaoCom['meses'],
            'horizonte_ate' => $projecaoSem['horizonte_ate'],
        ];
    }

    /**
     * @param  list<array{ano: int, mes: int, rotulo: string, receitas: float, despesas: float, saldo_projetado: float}>  $meses
     */
    private function estimarMesesAtePagar(float $valorOrcamento, array $meses): ?int
    {
        if ($valorOrcamento <= 0) {
            return 0;
        }

        foreach ($meses as $indice => $mes) {
            if ((float) $mes['saldo_projetado'] >= $valorOrcamento) {
                return $indice + 1;
            }
        }

        return null;
    }

    /**
     * @param  list<array{saldo_projetado: float}>  $meses
     */
    private function algumMesNegativo(array $meses): bool
    {
        foreach ($meses as $mes) {
            if ((float) $mes['saldo_projetado'] < 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{rotulo: string, saldo_projetado: float}>  $meses
     * @return array{rotulo: string, saldo_projetado: float}|null
     */
    private function ultimoMes(array $meses): ?array
    {
        if ($meses === []) {
            return null;
        }

        return $meses[array_key_last($meses)];
    }

    private function mensagemPrincipal(
        float $valor,
        float $saldoMesAtual,
        bool $pagoAgora,
        ?int $mesesAtePagar,
        bool $expirado,
    ): string {
        if ($expirado) {
            return 'Este orçamento já passou da data de validade.';
        }

        if ($pagoAgora) {
            return sprintf(
                'Você já consegue pagar este orçamento de R$ %s neste mês, mantendo os lançamentos previstos (saldo projetado do mês: R$ %s).',
                $this->moeda($valor),
                $this->moeda($saldoMesAtual),
            );
        }

        if ($mesesAtePagar !== null) {
            $rotuloMeses = $mesesAtePagar === 1
                ? '1 mês'
                : $mesesAtePagar.' meses';

            return sprintf(
                'Você poderá pagar esse orçamento integralmente em aproximadamente %s, mantendo os lançamentos previstos.',
                $rotuloMeses,
            );
        }

        return sprintf(
            'Com os lançamentos previstos no horizonte analisado, ainda não há previsão de acumular os R$ %s necessários para este orçamento.',
            $this->moeda($valor),
        );
    }

    private function mensagemDisponivel(float $saldoAtual, float $disponivel): string
    {
        return sprintf(
            'Embora suas contas tenham R$ %s, somente R$ %s estão disponíveis para assumir um novo compromisso sem comprometer seu fluxo financeiro.',
            $this->moeda($saldoAtual),
            $this->moeda($disponivel),
        );
    }

    private function moeda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
