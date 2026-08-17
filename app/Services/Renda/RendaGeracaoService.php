<?php

namespace App\Services\Renda;

use App\Enum\FormaPagamento;
use App\Enum\FrequenciaRecorrencia;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Models\Lancamento\Lancamento;
use App\Models\Renda\Renda;
use App\Repositories\Lancamento\LancamentoRepository;
use App\Repositories\Renda\RendaRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RendaGeracaoService
{
    public function __construct(
        private readonly RendaRepository $rendaRepository,
        private readonly LancamentoRepository $lancamentoRepository,
    ) {}

    public function gerarParaMes(int $idUsuario, int $ano, int $mes): void
    {
        $rendas = $this->rendaRepository->listarAtivasPorUsuario($idUsuario);

        foreach ($rendas as $renda) {
            $this->gerarOcorrenciasDoMes($renda, $ano, $mes);
        }
    }

    public function gerarOcorrenciasDoMes(Renda $renda, int $ano, int $mes): void
    {
        foreach ($this->datasNoMes($renda, $ano, $mes) as $data) {
            if ($this->lancamentoRepository->existeOcorrenciaDeRendaNaData(
                (int) $renda->id_renda,
                $data
            )) {
                continue;
            }

            $this->criarOcorrencia($renda, $data);
        }
    }

    public function atualizarPrevistosFuturos(Renda $renda): void
    {
        $hoje = Carbon::today()->startOfDay();

        $previstos = Lancamento::query()
            ->where('id_renda', $renda->id_renda)
            ->where('situacao', SituacaoLancamento::Previsto)
            ->whereDate('data_vencimento', '>=', $hoje->toDateString())
            ->get();

        foreach ($previstos as $lancamento) {
            $dia = min(
                (int) $renda->dia_esperado,
                (int) $lancamento->data_vencimento->daysInMonth
            );

            $novaData = $lancamento->data_vencimento->copy()->day($dia);

            $this->lancamentoRepository->atualizar($lancamento, [
                'descricao' => $renda->descricao,
                'valor' => $renda->valor_esperado,
                'valor_previsto' => $renda->valor_esperado,
                'id_conta_bancaria' => $renda->id_conta_bancaria,
                'data_vencimento' => $novaData->toDateString(),
                'observacao' => $renda->observacao,
            ]);
        }
    }

    public function cancelarPrevistos(Renda $renda): void
    {
        Lancamento::query()
            ->where('id_renda', $renda->id_renda)
            ->where('situacao', SituacaoLancamento::Previsto)
            ->update(['situacao' => SituacaoLancamento::Cancelado]);
    }

    private function datasNoMes(Renda $renda, int $ano, int $mes): Collection
    {
        $inicioMes = Carbon::create($ano, $mes, 1)->startOfDay();
        $fimMes = $inicioMes->copy()->endOfMonth()->startOfDay();
        $datas = collect();

        $cursor = Carbon::parse($renda->data_inicio)->startOfDay();

        if ($cursor->gt($fimMes)) {
            return $datas;
        }

        while ($cursor->lt($inicioMes)) {
            $cursor = $this->avancarData($cursor, $renda->frequencia);
        }

        while ($cursor->lte($fimMes)) {
            $datas->push($cursor->copy());
            $cursor = $this->avancarData($cursor, $renda->frequencia);
        }

        return $datas;
    }

    private function criarOcorrencia(Renda $renda, Carbon $data): Lancamento
    {
        return $this->lancamentoRepository->criar([
            'id_usuario' => $renda->id_usuario,
            'descricao' => $renda->descricao,
            'valor' => $renda->valor_esperado,
            'valor_previsto' => $renda->valor_esperado,
            'data_vencimento' => $data->toDateString(),
            'tipo' => TipoLancamento::Receita,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $renda->id_conta_bancaria,
            'situacao' => SituacaoLancamento::Previsto,
            'id_renda' => $renda->id_renda,
            'observacao' => $renda->observacao,
            'eh_recorrencia' => SimNao::Nao,
        ]);
    }

    private function avancarData(Carbon $data, FrequenciaRecorrencia $frequencia): Carbon
    {
        return match ($frequencia) {
            FrequenciaRecorrencia::Mensal => $data->copy()->addMonthNoOverflow(),
            FrequenciaRecorrencia::Semanal => $data->copy()->addWeek(),
            FrequenciaRecorrencia::Anual => $data->copy()->addYearNoOverflow(),
        };
    }
}
