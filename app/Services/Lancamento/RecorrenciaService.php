<?php

namespace App\Services\Lancamento;

use App\Enum\FormaPagamento;
use App\Enum\FrequenciaRecorrencia;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\Lancamento\Lancamento;
use App\Repositories\Lancamento\LancamentoRepository;
use App\Services\FaturaCartao\FaturaCartaoService;
use Carbon\Carbon;

class RecorrenciaService
{
    public function __construct(
        private readonly LancamentoRepository $lancamentoRepository,
        private readonly FaturaCartaoService $faturaCartaoService,
    ) {}

    public function gerarParaMes(int $idUsuario, int $ano, int $mes): void
    {
        $limite = Carbon::create($ano, $mes, 1)->endOfMonth()->startOfDay();
        $pais = $this->lancamentoRepository->buscarPaisRecorrentesAtivos($idUsuario);

        foreach ($pais as $pai) {
            $this->gerarOcorrenciasAte($pai, $limite);
        }
    }

    public function gerarOcorrenciasAte(Lancamento $pai, Carbon $limite): void
    {
        if (!$pai->ehPaiRecorrencia() || !$pai->frequencia_recorrencia) {
            return;
        }

        $ate = $pai->recorrencia_ate
            ? Carbon::parse($pai->recorrencia_ate)->min($limite)
            : $limite;

        $cursor = $pai->recorrencia_gerada_ate
            ? Carbon::parse($pai->recorrencia_gerada_ate)
            : Carbon::parse($pai->data_vencimento)->subDay();

        $proximo = $this->proximaData(
            Carbon::parse($pai->data_vencimento),
            $pai->frequencia_recorrencia,
            $pai->intervalo_dias,
            $cursor
        );

        $geradoAte = $pai->recorrencia_gerada_ate
            ? Carbon::parse($pai->recorrencia_gerada_ate)
            : null;

        while ($proximo->lte($ate)) {
            if (!$this->lancamentoRepository->existeOcorrenciaNaData((int) $pai->id_lancamento, $proximo)) {
                $this->criarOcorrencia($pai, $proximo);
            }

            $geradoAte = $proximo->copy();
            $proximo = $this->avancarData($proximo, $pai->frequencia_recorrencia, $pai->intervalo_dias);
        }

        if ($geradoAte) {
            $this->lancamentoRepository->atualizar($pai, [
                'recorrencia_gerada_ate' => $geradoAte->toDateString(),
            ]);
        }
    }

    private function criarOcorrencia(Lancamento $pai, Carbon $data): Lancamento
    {
        $dados = [
            'id_usuario' => $pai->id_usuario,
            'id_lancamento_pai' => $pai->id_lancamento,
            'descricao' => $pai->descricao,
            'valor' => $pai->valor,
            'data_vencimento' => $data->toDateString(),
            'tipo' => $pai->tipo,
            'forma_pagamento' => $pai->forma_pagamento,
            'id_conta_bancaria' => $pai->id_conta_bancaria,
            'id_cartao_credito' => $pai->id_cartao_credito,
            'situacao' => SituacaoLancamento::Pendente,
            'id_categoria' => $pai->id_categoria,
            'observacao' => $pai->observacao,
            'eh_recorrencia' => SimNao::Nao,
        ];

        if ($pai->forma_pagamento === FormaPagamento::CartaoCredito && $pai->id_cartao_credito) {
            $cartao = CartaoCredito::query()->find($pai->id_cartao_credito);
            if ($cartao) {
                $fatura = $this->faturaCartaoService->obterOuCriarParaData($cartao, $data);
                $dados['id_fatura_cartao'] = $fatura->id_fatura_cartao;
            }
        }

        return $this->lancamentoRepository->criar($dados);
    }

    private function proximaData(Carbon $inicio, FrequenciaRecorrencia $frequencia, ?int $intervaloDias, Carbon $apos): Carbon {
        $candidato = $inicio->copy();

        while ($candidato->lte($apos)) {
            $candidato = $this->avancarData($candidato, $frequencia, $intervaloDias);
        }

        return $candidato;
    }

    private function avancarData(Carbon $data, FrequenciaRecorrencia $frequencia, ?int $intervaloDias): Carbon
    {
        return match ($frequencia) {
            FrequenciaRecorrencia::Mensal => $data->copy()->addMonthNoOverflow(),
            FrequenciaRecorrencia::Semanal => $data->copy()->addWeek(),
            FrequenciaRecorrencia::Anual => $data->copy()->addYearNoOverflow(),
            FrequenciaRecorrencia::ACadaXDias => $data->copy()->addDays(max(1, (int) $intervaloDias)),
        };
    }
}
