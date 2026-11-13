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
    private const FORMATO_CHAVE_MES = 'Y-m';

    public function __construct(
        private readonly RendaRepository $rendaRepository,
        private readonly LancamentoRepository $lancamentoRepository,
    ) {}

    public function gerarParaMes(int $idUsuario, int $ano, int $mes): void
    {
        $rendas = $this->rendaRepository->listarAtivasPorUsuario($idUsuario);

        if ($rendas->isEmpty()) {
            return;
        }

        [$inicioMes, $fimMes] = $this->limitesDoMes($ano, $mes);
        $ocorrenciasPorRenda = $this->lancamentoRepository
            ->listarOcorrenciasDeRendasNoPeriodo($rendas->pluck('id_renda')->all(), $inicioMes, $fimMes)
            ->groupBy('id_renda');

        foreach ($rendas as $renda) {
            $this->criarOcorrenciasFaltantes(
                $renda,
                $this->datasNoMes($renda, $ano, $mes),
                $ocorrenciasPorRenda->get($renda->id_renda, collect()),
            );
        }
    }

    public function gerarOcorrenciasDoMes(Renda $renda, int $ano, int $mes): void
    {
        [$inicioMes, $fimMes] = $this->limitesDoMes($ano, $mes);
        $ocorrenciasDoMes = $this->lancamentoRepository
            ->listarOcorrenciasDeRendasNoPeriodo([(int) $renda->id_renda], $inicioMes, $fimMes);

        $this->criarOcorrenciasFaltantes($renda, $this->datasNoMes($renda, $ano, $mes), $ocorrenciasDoMes);
    }

    public function sincronizarPrevistosComRenda(Renda $renda): void
    {
        $ocorrencias = $this->lancamentoRepository->listarOcorrenciasAtivasDaRenda((int) $renda->id_renda);
        $ocorrenciasPorMes = $ocorrencias->groupBy(
            fn (Lancamento $lancamento) => $lancamento->data_vencimento->format(self::FORMATO_CHAVE_MES),
        );

        $mesesParaSincronizar = $ocorrencias
            ->toBase()
            ->filter(fn (Lancamento $lancamento) => $this->estaPrevisto($lancamento))
            ->map(fn (Lancamento $lancamento) => $lancamento->data_vencimento->format(self::FORMATO_CHAVE_MES))
            ->push(Carbon::today()->format(self::FORMATO_CHAVE_MES))
            ->unique()
            ->values();

        $idsPrevistosExcedentes = [];

        foreach ($mesesParaSincronizar as $chaveMes) {
            $idsPrevistosExcedentes = [
                ...$idsPrevistosExcedentes,
                ...$this->sincronizarPrevistosDoMes($renda, $chaveMes, $ocorrenciasPorMes->get($chaveMes, collect())),
            ];
        }

        $this->lancamentoRepository->excluirPrevistosDaRendaPorIds((int) $renda->id_renda, $idsPrevistosExcedentes);
    }

    public function cancelarPrevistos(Renda $renda): void
    {
        Lancamento::query()
            ->where('id_renda', $renda->id_renda)
            ->where('situacao', SituacaoLancamento::Previsto)
            ->update(['situacao' => SituacaoLancamento::Cancelado]);
    }

    private function sincronizarPrevistosDoMes(Renda $renda, string $chaveMes, Collection $ocorrenciasDoMes): array
    {
        [$ano, $mes] = array_map('intval', explode('-', $chaveMes));

        $previstosDoMes = $ocorrenciasDoMes
            ->filter(fn (Lancamento $lancamento) => $this->estaPrevisto($lancamento))
            ->values();
        $efetivadosDoMes = $ocorrenciasDoMes
            ->reject(fn (Lancamento $lancamento) => $this->estaPrevisto($lancamento));

        $datasLivres = $this->removerDatasJaOcupadas($this->datasNoMes($renda, $ano, $mes), $efetivadosDoMes);

        foreach ($datasLivres as $indice => $data) {
            $previstoReaproveitado = $previstosDoMes->get($indice);

            if ($previstoReaproveitado) {
                $this->lancamentoRepository->atualizar($previstoReaproveitado, $this->montarDadosOcorrencia($renda, $data));

                continue;
            }

            $this->criarOcorrencia($renda, $data);
        }

        return $previstosDoMes
            ->slice($datasLivres->count())
            ->map(fn (Lancamento $lancamento) => (int) $lancamento->id_lancamento)
            ->values()
            ->all();
    }

    private function criarOcorrenciasFaltantes(Renda $renda, Collection $datasDoMes, Collection $ocorrenciasDoMes): void
    {
        foreach ($this->removerDatasJaOcupadas($datasDoMes, $ocorrenciasDoMes) as $data) {
            $this->criarOcorrencia($renda, $data);
        }
    }

    private function removerDatasJaOcupadas(Collection $datasEsperadas, Collection $ocorrenciasExistentes): Collection
    {
        $datasLivres = $datasEsperadas->values()->all();

        foreach ($ocorrenciasExistentes as $ocorrencia) {
            if ($datasLivres === []) {
                break;
            }

            $dataOcorrencia = Carbon::parse($ocorrencia->data_vencimento)->startOfDay();
            $indiceMaisProximo = null;
            $menorDistancia = null;

            foreach ($datasLivres as $indice => $dataEsperada) {
                $distancia = abs($dataEsperada->diffInDays($dataOcorrencia));

                if ($menorDistancia === null || $distancia < $menorDistancia) {
                    $menorDistancia = $distancia;
                    $indiceMaisProximo = $indice;
                }
            }

            unset($datasLivres[$indiceMaisProximo]);
        }

        return collect(array_values($datasLivres));
    }

    private function estaPrevisto(Lancamento $lancamento): bool
    {
        return $lancamento->situacao === SituacaoLancamento::Previsto;
    }

    private function limitesDoMes(int $ano, int $mes): array
    {
        $inicioMes = Carbon::create($ano, $mes, 1)->startOfDay();

        return [$inicioMes, $inicioMes->copy()->endOfMonth()->startOfDay()];
    }

    private function datasNoMes(Renda $renda, int $ano, int $mes): Collection
    {
        [$inicioMes, $fimMes] = $this->limitesDoMes($ano, $mes);
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

    private function montarDadosOcorrencia(Renda $renda, Carbon $data): array
    {
        return [
            'descricao' => $renda->descricao,
            'valor' => $renda->valor_esperado,
            'valor_previsto' => $renda->valor_esperado,
            'id_conta_bancaria' => $renda->id_conta_bancaria,
            'data_vencimento' => $data->toDateString(),
            'observacao' => $renda->observacao,
        ];
    }

    private function criarOcorrencia(Renda $renda, Carbon $data): Lancamento
    {
        return $this->lancamentoRepository->criar([
            ...$this->montarDadosOcorrencia($renda, $data),
            'id_usuario' => $renda->id_usuario,
            'tipo' => TipoLancamento::Receita,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'situacao' => SituacaoLancamento::Previsto,
            'id_renda' => $renda->id_renda,
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
