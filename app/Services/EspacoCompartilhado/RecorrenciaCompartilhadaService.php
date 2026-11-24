<?php

namespace App\Services\EspacoCompartilhado;

use App\Enum\AcaoAuditoriaCompartilhada;
use App\Enum\FrequenciaRecorrencia;
use App\Enum\StatusLancamentoCompartilhado;
use App\Enum\StatusRecorrenciaCompartilhada;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\EspacoCompartilhado\RecorrenciaCompartilhada;
use App\Repositories\EspacoCompartilhado\LancamentoCompartilhadoRepository;
use App\Repositories\EspacoCompartilhado\RecorrenciaCompartilhadaRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

class RecorrenciaCompartilhadaService
{
    private const LIMITE_OCORRENCIAS_POR_GERACAO = 1000;

    public function __construct(
        private readonly RecorrenciaCompartilhadaRepository $recorrenciaRepository,
        private readonly LancamentoCompartilhadoRepository $lancamentoCompartilhadoRepository,
        private readonly AuditoriaEspacoCompartilhadoService $auditoriaService,
    ) {}

    public function gerarOcorrenciasAteOFimDoMes(int $idEspaco, int $ano, int $mes): void
    {
        $limite = Carbon::create($ano, $mes, 1)->endOfMonth()->startOfDay();
        $recorrencias = $this->recorrenciaRepository->listarRecorrenciasAtivasIniciadasAte($idEspaco, $limite);

        foreach ($recorrencias as $recorrencia) {
            $this->gerarOcorrenciasDaRecorrenciaAte($recorrencia, $limite);
        }
    }

    public function criarRecorrenciaCompartilhada(EspacoCompartilhado $espaco, int $idUsuario, array $dados): LancamentoCompartilhado
    {
        $dataInicio = Carbon::parse($dados['data_vencimento'])->startOfDay();

        $recorrencia = $this->recorrenciaRepository->criarRecorrenciaCompartilhada([
            'id_espaco_compartilhado' => (int) $espaco->id_espaco_compartilhado,
            'id_usuario_responsavel' => (int) $dados['id_usuario_responsavel'],
            'id_categoria' => (int) $dados['id_categoria'],
            'descricao' => $dados['descricao'],
            'valor' => $dados['valor'],
            'frequencia' => FrequenciaRecorrencia::from($dados['frequencia_recorrencia']),
            'data_inicio' => $dataInicio->toDateString(),
            'data_fim' => $dados['recorrencia_ate'] ?? null,
            'status' => StatusRecorrenciaCompartilhada::Ativa,
            'criado_por' => $idUsuario,
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            $idUsuario,
            AcaoAuditoriaCompartilhada::RecorrenciaCriada,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_RECORRENCIA,
            (int) $recorrencia->id_recorrencia_compartilhada,
            null,
            [
                'descricao' => $recorrencia->descricao,
                'valor' => (string) $recorrencia->valor,
                'frequencia' => $recorrencia->frequencia->value,
                'data_inicio' => $recorrencia->data_inicio?->toDateString(),
                'data_fim' => $recorrencia->data_fim?->toDateString(),
                'id_usuario_responsavel' => $recorrencia->id_usuario_responsavel,
            ],
        );

        $this->gerarOcorrenciasDaRecorrenciaAte($recorrencia, $dataInicio->copy()->endOfMonth()->startOfDay(), $idUsuario);

        return $this->lancamentoCompartilhadoRepository->buscarPrimeiraOcorrenciaDaRecorrencia(
            (int) $recorrencia->id_recorrencia_compartilhada
        );
    }

    public function atualizarModeloDaRecorrencia(LancamentoCompartilhado $ocorrencia, int $idUsuario): void
    {
        if (! $ocorrencia->pertenceARecorrencia()) {
            return;
        }

        $recorrencia = $this->recorrenciaRepository->bloquearRecorrenciaParaAtualizacao(
            (int) $ocorrencia->id_recorrencia_compartilhada
        );

        if (! $recorrencia || ! $recorrencia->estaAtiva()) {
            return;
        }

        $dadosAnteriores = [
            'descricao' => $recorrencia->descricao,
            'valor' => (string) $recorrencia->valor,
            'id_usuario_responsavel' => $recorrencia->id_usuario_responsavel,
            'id_categoria' => $recorrencia->id_categoria,
        ];

        $atualizada = $this->recorrenciaRepository->atualizarRecorrenciaCompartilhada($recorrencia, [
            'descricao' => $ocorrencia->descricao,
            'valor' => $ocorrencia->valor,
            'id_usuario_responsavel' => $ocorrencia->id_usuario_responsavel,
            'id_categoria' => $ocorrencia->id_categoria,
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $recorrencia->id_espaco_compartilhado,
            $idUsuario,
            AcaoAuditoriaCompartilhada::RecorrenciaAtualizada,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_RECORRENCIA,
            (int) $recorrencia->id_recorrencia_compartilhada,
            $dadosAnteriores,
            [
                'descricao' => $atualizada->descricao,
                'valor' => (string) $atualizada->valor,
                'id_usuario_responsavel' => $atualizada->id_usuario_responsavel,
                'id_categoria' => $atualizada->id_categoria,
            ],
        );
    }

    public function encerrarRecorrenciaAPartirDaOcorrencia(LancamentoCompartilhado $ocorrencia, int $idUsuario): void
    {
        if (! $ocorrencia->pertenceARecorrencia()) {
            return;
        }

        $recorrencia = $this->recorrenciaRepository->bloquearRecorrenciaParaAtualizacao(
            (int) $ocorrencia->id_recorrencia_compartilhada
        );

        if (! $recorrencia || ! $recorrencia->estaAtiva()) {
            return;
        }

        $dataDaOcorrencia = Carbon::parse($ocorrencia->data_vencimento)->startOfDay();
        $novaDataFim = $dataDaOcorrencia->copy()->subDay();

        $this->recorrenciaRepository->atualizarRecorrenciaCompartilhada($recorrencia, [
            'status' => StatusRecorrenciaCompartilhada::Encerrada,
            'data_fim' => $novaDataFim->lt($recorrencia->data_inicio) ? $recorrencia->data_inicio : $novaDataFim->toDateString(),
        ]);

        $quantidadeCanceladas = $this->lancamentoCompartilhadoRepository->cancelarOcorrenciasPendentesDaRecorrenciaAPartirDe(
            (int) $recorrencia->id_recorrencia_compartilhada,
            $dataDaOcorrencia
        );

        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $recorrencia->id_espaco_compartilhado,
            $idUsuario,
            AcaoAuditoriaCompartilhada::RecorrenciaEncerrada,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_RECORRENCIA,
            (int) $recorrencia->id_recorrencia_compartilhada,
            ['status' => StatusRecorrenciaCompartilhada::Ativa->value],
            [
                'status' => StatusRecorrenciaCompartilhada::Encerrada->value,
                'encerrada_a_partir_de' => $dataDaOcorrencia->toDateString(),
                'ocorrencias_canceladas' => $quantidadeCanceladas,
            ],
        );
    }

    private function gerarOcorrenciasDaRecorrenciaAte(
        RecorrenciaCompartilhada $recorrencia,
        CarbonInterface $limite,
        ?int $idUsuarioCriador = null,
    ): void {
        if (! $recorrencia->estaAtiva()) {
            return;
        }

        $ate = $recorrencia->data_fim && $recorrencia->data_fim->lt($limite)
            ? $recorrencia->data_fim->copy()->startOfDay()
            : $limite->copy()->startOfDay();

        if ($recorrencia->gerada_ate && $recorrencia->gerada_ate->gte($ate)) {
            return;
        }

        $dataInicio = $recorrencia->data_inicio->copy()->startOfDay();
        $ultimaDataGerada = null;

        for ($indice = 0; $indice < self::LIMITE_OCORRENCIAS_POR_GERACAO; $indice++) {
            $dataDaOcorrencia = $recorrencia->frequencia->calcularDataDaOcorrencia($dataInicio, $indice);

            if ($dataDaOcorrencia->gt($ate)) {
                break;
            }

            if ($recorrencia->gerada_ate && $dataDaOcorrencia->lte($recorrencia->gerada_ate)) {
                continue;
            }

            $this->criarOcorrenciaSeAusente($recorrencia, $dataDaOcorrencia, $idUsuarioCriador);
            $ultimaDataGerada = $dataDaOcorrencia;
        }

        if ($ultimaDataGerada) {
            $this->recorrenciaRepository->atualizarRecorrenciaCompartilhada($recorrencia, [
                'gerada_ate' => $ultimaDataGerada->toDateString(),
            ]);
        }
    }

    private function criarOcorrenciaSeAusente(
        RecorrenciaCompartilhada $recorrencia,
        CarbonInterface $dataDaOcorrencia,
        ?int $idUsuarioCriador,
    ): void {
        $idRecorrencia = (int) $recorrencia->id_recorrencia_compartilhada;

        if ($this->lancamentoCompartilhadoRepository->existeOcorrenciaDaRecorrenciaNaData($idRecorrencia, $dataDaOcorrencia)) {
            return;
        }

        try {
            $this->lancamentoCompartilhadoRepository->criarLancamentoCompartilhado([
                'id_espaco_compartilhado' => (int) $recorrencia->id_espaco_compartilhado,
                'id_recorrencia_compartilhada' => $idRecorrencia,
                'id_usuario_responsavel' => $recorrencia->id_usuario_responsavel,
                'id_categoria' => $recorrencia->id_categoria,
                'descricao' => $recorrencia->descricao,
                'valor' => $recorrencia->valor,
                'data_vencimento' => $dataDaOcorrencia->toDateString(),
                'status' => StatusLancamentoCompartilhado::Pendente,
                'versao' => 1,
                'criado_por' => $idUsuarioCriador ?? $recorrencia->criado_por,
            ]);
        } catch (UniqueConstraintViolationException) {
            return;
        }
    }
}
