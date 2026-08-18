<?php

namespace App\Services\Orcamento;

use App\Enum\FormaPagamento;
use App\Enum\ModalidadePagamentoOrcamento;
use Carbon\Carbon;

class CalculadoraViabilidadeOrcamentoServico
{
    /**
     * @param  array<string, mixed>  $projecaoSem
     * @param  array<string, mixed>  $projecaoCom
     * @param  array{
     *     modalidade: ModalidadePagamentoOrcamento,
     *     forma: FormaPagamento,
     *     total_parcelas: int,
     *     valor_parcela: float,
     *     limite_disponivel_cartao?: float|null,
     *     limite_total_cartao?: float|null,
     *     cartao_nome?: string|null,
     *     consome_limite_cartao?: bool,
     *     ultrapassa_limite_cartao?: bool
     * }  $contextoPagamento
     * @return array<string, mixed>
     */
    public function montarResumo(
        float $valorOrcamento,
        array $projecaoSem,
        array $projecaoCom,
        Carbon $dataValidade,
        array $contextoPagamento,
        ?Carbon $hoje = null,
    ): array {
        $hoje = ($hoje ?? Carbon::today())->copy()->startOfDay();
        $valorOrcamento = round(max(0, $valorOrcamento), 2);
        $disponivel = (float) $projecaoSem['saldo_disponivel_planejamento'];
        $saldoAtual = (float) $projecaoSem['saldo_atual_contas'];
        $saldoMesAtual = (float) (($projecaoSem['meses'][0]['saldo_projetado'] ?? $disponivel));
        $valorParcela = round((float) ($contextoPagamento['valor_parcela'] ?? $valorOrcamento), 2);
        $totalParcelas = max(1, (int) ($contextoPagamento['total_parcelas'] ?? 1));
        $modalidade = $contextoPagamento['modalidade'];
        $forma = $contextoPagamento['forma'];
        $ultrapassaLimite = (bool) ($contextoPagamento['ultrapassa_limite_cartao'] ?? false);

        $mesesAtePagar = $modalidade === ModalidadePagamentoOrcamento::Parcelado
            ? $this->estimarMesesAtePagarParcela($valorParcela, $projecaoSem['meses'])
            : $this->estimarMesesAtePagar($valorOrcamento, $projecaoSem['meses']);

        $comprometeFluxo = ((float) $projecaoCom['saldo_projetado_final']) < 0
            || $this->algumMesNegativo($projecaoCom['meses']);

        $pagoIntegralmenteAgora = $modalidade === ModalidadePagamentoOrcamento::Parcelado
            ? ($saldoMesAtual >= $valorParcela && $valorParcela > 0)
            : ($saldoMesAtual >= $valorOrcamento && $valorOrcamento > 0);

        $expirado = $dataValidade->copy()->startOfDay()->lt($hoje);
        $mesFinalSem = $this->ultimoMes($projecaoSem['meses']);
        $mesFinalCom = $this->ultimoMes($projecaoCom['meses']);

        $mensagemAlerta = null;
        if ($ultrapassaLimite) {
            $mensagemAlerta = sprintf(
                'Esse orçamento ultrapassa o limite disponível do cartão%s.',
                ! empty($contextoPagamento['cartao_nome'])
                    ? ' '.$contextoPagamento['cartao_nome']
                    : ''
            );
        } elseif ($comprometeFluxo) {
            $mensagemAlerta = 'Esse orçamento comprometeria seu fluxo de caixa futuro.';
        }

        return [
            'saldo_atual_contas' => $saldoAtual,
            'saldo_atual_contas_formatado' => $this->moeda($saldoAtual),
            'saldo_disponivel_planejamento' => $disponivel,
            'saldo_disponivel_planejamento_formatado' => $this->moeda($disponivel),
            'receitas_previstas' => (float) $projecaoSem['receitas_previstas'],
            'despesas_previstas' => (float) $projecaoSem['despesas_previstas'],
            'pago_integralmente_agora' => $pagoIntegralmenteAgora && ! $ultrapassaLimite,
            'meses_ate_pagar' => $mesesAtePagar,
            'compromete_fluxo' => $comprometeFluxo || $ultrapassaLimite,
            'expirado' => $expirado,
            'valor_parcela' => $valorParcela,
            'valor_parcela_formatado' => $this->moeda($valorParcela),
            'total_parcelas' => $totalParcelas,
            'limite_disponivel_cartao' => $contextoPagamento['limite_disponivel_cartao'] ?? null,
            'limite_disponivel_cartao_formatado' => isset($contextoPagamento['limite_disponivel_cartao'])
                ? $this->moeda((float) $contextoPagamento['limite_disponivel_cartao'])
                : null,
            'ultrapassa_limite_cartao' => $ultrapassaLimite,
            'mensagem_principal' => $this->mensagemPrincipal(
                $valorOrcamento,
                $saldoMesAtual,
                $pagoIntegralmenteAgora,
                $mesesAtePagar,
                $expirado,
                $modalidade,
                $forma,
                $totalParcelas,
                $valorParcela,
                $ultrapassaLimite,
                $contextoPagamento['cartao_nome'] ?? null,
            ),
            'mensagem_disponivel' => $this->mensagemDisponivel(
                $saldoAtual,
                $disponivel,
                $this->mediaLiquidaMensal($projecaoSem['meses'] ?? []),
                $forma,
                $contextoPagamento['limite_disponivel_cartao'] ?? null,
                $contextoPagamento['cartao_nome'] ?? null,
            ),
            'mensagem_alerta' => $mensagemAlerta,
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
     * @param  list<array{
     *     total_parcelas: int,
     *     projecao_com: array<string, mixed>,
     *     contexto: array<string, mixed>
     * }>  $cenarios
     * @return array<string, mixed>
     */
    public function simularCenarios(
        float $valorOrcamento,
        array $projecaoSem,
        Carbon $dataValidade,
        array $cenarios,
        ModalidadePagamentoOrcamento $modalidadeEscolhida,
        ?Carbon $hoje = null,
        ?float $saldoContaSelecionada = null,
    ): array {
        $hoje = ($hoje ?? Carbon::today())->copy()->startOfDay();
        $liquidoMedio = $this->mediaLiquidaMensal($projecaoSem['meses'] ?? []);
        $saldoAtual = (float) $projecaoSem['saldo_atual_contas'];
        $saldoPagamentoConta = $saldoContaSelecionada ?? $saldoAtual;
        $expirado = $dataValidade->copy()->startOfDay()->lt($hoje);

        $resultados = [];

        foreach ($cenarios as $cenario) {
            $totalParcelas = max(1, (int) $cenario['total_parcelas']);
            $contexto = $cenario['contexto'];
            $projecaoCom = $cenario['projecao_com'];
            $forma = $contexto['forma'];
            $valorParcela = round((float) ($contexto['valor_parcela'] ?? $valorOrcamento), 2);
            $ultrapassaLimite = (bool) ($contexto['ultrapassa_limite_cartao'] ?? false);

            $mesesCom = $projecaoCom['meses'] ?? [];
            $comprometeMesAtual = $this->mesAtualNegativo($mesesCom);
            $comprometeMesesSeguintes = $this->mesesSeguintesNegativos($mesesCom);
            $comprometeFluxo = ((float) $projecaoCom['saldo_projetado_final']) < 0
                || $comprometeMesAtual
                || $comprometeMesesSeguintes;

            $aVista = $totalParcelas === 1;
            $saldoCapacidade = $forma === FormaPagamento::ContaBancaria
                ? $saldoPagamentoConta
                : $saldoAtual;
            $dentroCapacidade = $this->dentroCapacidade(
                $aVista,
                $valorOrcamento,
                $valorParcela,
                $saldoCapacidade,
                $liquidoMedio,
                $forma,
            );

            $viavel = ! $comprometeFluxo && ! $ultrapassaLimite && $dentroCapacidade;

            $mesFinalSem = $this->ultimoMes($projecaoSem['meses']);
            $mesFinalCom = $this->ultimoMes($projecaoCom['meses']);

            $resultados[] = [
                'rotulo' => $aVista
                    ? 'À vista'
                    : sprintf('%dx de R$ %s', $totalParcelas, $this->moeda($valorParcela)),
                'modalidade_pagamento' => $aVista
                    ? ModalidadePagamentoOrcamento::AVista->value
                    : ModalidadePagamentoOrcamento::Parcelado->value,
                'total_parcelas' => $totalParcelas,
                'valor_parcela' => $valorParcela,
                'valor_parcela_formatado' => $this->moeda($valorParcela),
                'impacto_imediato' => $aVista ? $valorOrcamento : $valorParcela,
                'impacto_imediato_formatado' => $this->moeda($aVista ? $valorOrcamento : $valorParcela),
                'impacto_total' => $valorOrcamento,
                'forma_pagamento' => $forma->value,
                'dentro_capacidade_mensal' => $dentroCapacidade,
                'compromete_fluxo' => $comprometeFluxo || $ultrapassaLimite,
                'compromete_mes_atual' => $comprometeMesAtual,
                'compromete_meses_seguintes' => $comprometeMesesSeguintes,
                'rotulo_fluxo' => $this->rotuloFluxo(
                    $comprometeMesAtual,
                    $comprometeMesesSeguintes,
                    $ultrapassaLimite,
                ),
                'ultrapassa_limite_cartao' => $ultrapassaLimite,
                'viavel' => $viavel && ! $expirado,
                'recomendado' => false,
                'comparativo' => [
                    'mes_rotulo' => $mesFinalSem['rotulo'] ?? null,
                    'saldo_sem_orcamento' => (float) ($mesFinalSem['saldo_projetado'] ?? 0),
                    'saldo_sem_orcamento_formatado' => $this->moeda((float) ($mesFinalSem['saldo_projetado'] ?? 0)),
                    'saldo_com_orcamento' => (float) ($mesFinalCom['saldo_projetado'] ?? 0),
                    'saldo_com_orcamento_formatado' => $this->moeda((float) ($mesFinalCom['saldo_projetado'] ?? 0)),
                ],
            ];
        }

        $recomendado = $this->escolherCenarioRecomendado($resultados);
        if ($recomendado !== null) {
            $resultados[$recomendado]['recomendado'] = true;
        }

        $podeAssumir = $this->avaliarPodeAssumir($resultados, $modalidadeEscolhida, $expirado);
        $cenarioReferencia = $this->cenarioReferencia($resultados, $modalidadeEscolhida);
        $receitaMensal = $this->mediaReceitasMensal($projecaoSem['meses'] ?? []);
        $limiteDisponivel = $cenarios[0]['contexto']['limite_disponivel_cartao'] ?? null;

        return [
            'saldo_atual_contas' => $saldoAtual,
            'saldo_atual_contas_formatado' => $this->moeda($saldoAtual),
            'saldo_conta_selecionada' => $saldoContaSelecionada,
            'saldo_conta_selecionada_formatado' => $saldoContaSelecionada !== null
                ? $this->moeda($saldoContaSelecionada)
                : null,
            'liquido_medio_mensal' => $liquidoMedio,
            'liquido_medio_mensal_formatado' => $this->moeda($liquidoMedio),
            'receita_prevista_mensal' => $receitaMensal,
            'receita_prevista_mensal_formatado' => $this->moeda($receitaMensal),
            'receitas_previstas' => (float) $projecaoSem['receitas_previstas'],
            'despesas_previstas' => (float) $projecaoSem['despesas_previstas'],
            'limite_disponivel_cartao' => $limiteDisponivel,
            'limite_disponivel_cartao_formatado' => $limiteDisponivel !== null
                ? $this->moeda((float) $limiteDisponivel)
                : null,
            'expirado' => $expirado,
            'pode_assumir_compromisso' => $podeAssumir,
            'resumo_compromisso' => $this->montarResumoCompromisso(
                $podeAssumir,
                $modalidadeEscolhida,
                $cenarioReferencia,
                $expirado,
            ),
            'cenario_referencia' => $cenarioReferencia,
            'cenarios' => $resultados,
            'horizonte_ate' => $projecaoSem['horizonte_ate'],
        ];
    }

    /**
     * @param  list<array{liquido?: float, receitas: float, despesas: float}>  $meses
     */
    public function mediaLiquidaMensal(array $meses): float
    {
        if ($meses === []) {
            return 0.0;
        }

        $liquidos = array_map(function (array $mes) {
            if (isset($mes['liquido'])) {
                return round((float) $mes['liquido'], 2);
            }

            return round((float) $mes['receitas'] - (float) $mes['despesas'], 2);
        }, $meses);

        $comMovimento = array_values(array_filter($liquidos, fn (float $v) => abs($v) > 0.009));

        if ($comMovimento === []) {
            return 0.0;
        }

        return round(array_sum($comMovimento) / count($comMovimento), 2);
    }

    /**
     * @param  list<array{receitas: float}>  $meses
     */
    public function mediaReceitasMensal(array $meses): float
    {
        if ($meses === []) {
            return 0.0;
        }

        $receitas = array_map(
            fn (array $mes) => round((float) ($mes['receitas'] ?? 0), 2),
            $meses,
        );

        $comReceita = array_values(array_filter($receitas, fn (float $v) => $v > 0.009));

        if ($comReceita === []) {
            return 0.0;
        }

        return round(array_sum($comReceita) / count($comReceita), 2);
    }

    private function dentroCapacidade(
        bool $aVista,
        float $valorTotal,
        float $valorParcela,
        float $saldoAtual,
        float $liquidoMedio,
        FormaPagamento $forma,
    ): bool {
        if ($aVista && $forma === FormaPagamento::ContaBancaria) {
            return $saldoAtual >= $valorTotal;
        }

        if ($aVista) {
            return $liquidoMedio >= $valorTotal || $saldoAtual >= $valorTotal;
        }

        return $valorParcela <= $liquidoMedio;
    }

    /**
     * @param  list<array<string, mixed>>  $cenarios
     */
    private function avaliarPodeAssumir(
        array $cenarios,
        ModalidadePagamentoOrcamento $modalidade,
        bool $expirado,
    ): bool {
        if ($expirado || $cenarios === []) {
            return false;
        }

        if ($modalidade === ModalidadePagamentoOrcamento::AVista) {
            return (bool) ($cenarios[0]['viavel'] ?? false);
        }

        foreach ($cenarios as $cenario) {
            if (($cenario['total_parcelas'] ?? 1) > 1 && ($cenario['viavel'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $cenarios
     * @return array<string, mixed>|null
     */
    private function cenarioReferencia(
        array $cenarios,
        ModalidadePagamentoOrcamento $modalidade,
    ): ?array {
        if ($cenarios === []) {
            return null;
        }

        if ($modalidade === ModalidadePagamentoOrcamento::AVista) {
            return $cenarios[0];
        }

        foreach ($cenarios as $cenario) {
            if ($cenario['recomendado'] ?? false) {
                return $cenario;
            }
        }

        return $cenarios[array_key_last($cenarios)];
    }

    /**
     * @param  array<string, mixed>|null  $cenario
     */
    private function montarResumoCompromisso(
        bool $podeAssumir,
        ModalidadePagamentoOrcamento $modalidade,
        ?array $cenario,
        bool $expirado,
    ): string {
        if ($expirado) {
            return 'Esta cotação expirou e não pode ser assumida.';
        }

        if ($podeAssumir) {
            if ($modalidade === ModalidadePagamentoOrcamento::AVista) {
                return 'Você pode assumir este compromisso à vista com base no seu fluxo financeiro.';
            }

            $rotulo = $cenario['rotulo'] ?? 'parcelamento recomendado';

            return sprintf(
                'Você pode assumir este compromisso. Opção sugerida: %s.',
                $rotulo,
            );
        }

        if ($cenario && ($cenario['ultrapassa_limite_cartao'] ?? false)) {
            return 'Não é recomendado assumir agora: o valor ultrapassa o limite disponível do cartão.';
        }

        if ($cenario && ($cenario['compromete_fluxo'] ?? false)) {
            return 'Não é recomendado assumir agora: este gasto comprometeria seu fluxo de caixa.';
        }

        return 'Não é recomendado assumir este compromisso com a forma de pagamento escolhida.';
    }

    /**
     * @param  list<array<string, mixed>>  $cenarios
     */
    private function escolherCenarioRecomendado(array $cenarios): ?int
    {
        $melhor = null;
        $melhorParcelas = 0;

        foreach ($cenarios as $indice => $cenario) {
            if (! ($cenario['viavel'] ?? false)) {
                continue;
            }

            $parcelas = (int) ($cenario['total_parcelas'] ?? 1);
            if ($parcelas <= $melhorParcelas) {
                continue;
            }

            $melhor = $indice;
            $melhorParcelas = $parcelas;
        }

        return $melhor;
    }

    /**
     * @param  list<array{saldo_projetado: float}>  $meses
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
    private function estimarMesesAtePagarParcela(float $valorParcela, array $meses): ?int
    {
        if ($valorParcela <= 0) {
            return 0;
        }

        foreach ($meses as $indice => $mes) {
            if ((float) $mes['saldo_projetado'] >= $valorParcela) {
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
     * @param  list<array{saldo_projetado: float}>  $meses
     */
    private function mesAtualNegativo(array $meses): bool
    {
        if ($meses === []) {
            return false;
        }

        return (float) ($meses[0]['saldo_projetado'] ?? 0) < 0;
    }

    /**
     * @param  list<array{saldo_projetado: float}>  $meses
     */
    private function mesesSeguintesNegativos(array $meses): bool
    {
        foreach (array_slice($meses, 1) as $mes) {
            if ((float) ($mes['saldo_projetado'] ?? 0) < 0) {
                return true;
            }
        }

        return false;
    }

    private function rotuloFluxo(
        bool $comprometeMesAtual,
        bool $comprometeMesesSeguintes,
        bool $ultrapassaLimite,
    ): string {
        if ($ultrapassaLimite) {
            return 'Ultrapassa o limite';
        }

        if ($comprometeMesAtual && $comprometeMesesSeguintes) {
            return 'Compromete o mês atual e os seguintes';
        }

        if ($comprometeMesAtual) {
            return 'Compromete o mês atual';
        }

        if ($comprometeMesesSeguintes) {
            return 'Compromete meses seguintes';
        }

        return 'Ok';
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
        ModalidadePagamentoOrcamento $modalidade,
        FormaPagamento $forma,
        int $totalParcelas,
        float $valorParcela,
        bool $ultrapassaLimite,
        ?string $cartaoNome,
    ): string {
        if ($expirado) {
            return 'Este orçamento já passou da data de validade.';
        }

        if ($ultrapassaLimite) {
            return sprintf(
                'O valor de R$ %s não cabe no limite disponível do cartão%s.',
                $this->moeda($valor),
                $cartaoNome ? ' '.$cartaoNome : ''
            );
        }

        if ($modalidade === ModalidadePagamentoOrcamento::Parcelado) {
            if ($pagoAgora) {
                return sprintf(
                    'Em %dx de R$ %s%s, a parcela cabe neste mês (saldo projetado: R$ %s), mantendo os lançamentos previstos.',
                    $totalParcelas,
                    $this->moeda($valorParcela),
                    $forma === FormaPagamento::CartaoCredito
                        ? ' no cartão'.($cartaoNome ? ' '.$cartaoNome : '')
                        : ' no PIX/dinheiro',
                    $this->moeda($saldoMesAtual),
                );
            }

            if ($mesesAtePagar !== null) {
                $rotuloMeses = $mesesAtePagar === 1 ? '1 mês' : $mesesAtePagar.' meses';

                return sprintf(
                    'Em %dx de R$ %s, a parcela passa a caber no fluxo em aproximadamente %s, mantendo os lançamentos previstos.',
                    $totalParcelas,
                    $this->moeda($valorParcela),
                    $rotuloMeses,
                );
            }

            return sprintf(
                'Com os lançamentos previstos, as parcelas de R$ %s ainda comprometem o fluxo no horizonte analisado.',
                $this->moeda($valorParcela),
            );
        }

        if ($pagoAgora) {
            return sprintf(
                'Você já consegue pagar este orçamento de R$ %s %s neste mês, mantendo os lançamentos previstos (saldo projetado do mês: R$ %s).',
                $this->moeda($valor),
                $forma === FormaPagamento::CartaoCredito
                    ? 'no cartão'.($cartaoNome ? ' '.$cartaoNome : '')
                    : 'à vista',
                $this->moeda($saldoMesAtual),
            );
        }

        if ($mesesAtePagar !== null) {
            $rotuloMeses = $mesesAtePagar === 1 ? '1 mês' : $mesesAtePagar.' meses';

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

    private function mensagemDisponivel(
        float $saldoAtual,
        float $disponivel,
        float $liquidoMedio,
        FormaPagamento $forma,
        ?float $limiteDisponivelCartao,
        ?string $cartaoNome,
    ): string {
        $base = sprintf(
            'Suas contas têm R$ %s hoje. O líquido médio previsto (receitas − despesas em conta e cartão) é de R$ %s por mês.',
            $this->moeda($saldoAtual),
            $this->moeda($liquidoMedio),
        );

        if ($forma === FormaPagamento::CartaoCredito && $limiteDisponivelCartao !== null) {
            return $base.sprintf(
                ' Para pagamento no cartão%s, o limite disponível é R$ %s (não entra como saldo em conta).',
                $cartaoNome ? ' '.$cartaoNome : '',
                $this->moeda($limiteDisponivelCartao),
            );
        }

        return $base;
    }

    private function moeda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
