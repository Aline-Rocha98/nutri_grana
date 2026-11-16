<?php

namespace App\Services\EspacoCompartilhado;

use App\Enum\AcaoAuditoriaCompartilhada;
use App\Enum\PapelMembroEspaco;
use App\Enum\StatusEspacoCompartilhado;
use App\Enum\StatusMembroEspaco;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Repositories\EspacoCompartilhado\EspacoCompartilhadoRepository;
use App\Repositories\EspacoCompartilhado\MembroEspacoCompartilhadoRepository;
use Illuminate\Support\Collection;

class EspacoCompartilhadoService
{
    public function __construct(
        private readonly EspacoCompartilhadoRepository $espacoRepository,
        private readonly MembroEspacoCompartilhadoRepository $membroEspacoRepository,
        private readonly AuditoriaEspacoCompartilhadoService $auditoriaService,
    ) {}

    public function listarEspacosDoUsuario(int $idUsuario): Collection
    {
        return $this->espacoRepository->listarEspacosAtivosDoUsuario($idUsuario);
    }

    public function listarOpcoesDeEspacosDoUsuario(int $idUsuario): Collection
    {
        return $this->espacoRepository->listarOpcoesDeEspacosDoUsuario($idUsuario);
    }

    public function criarEspacoCompartilhado(int $idUsuario, array $dados): EspacoCompartilhado
    {
        $espaco = $this->espacoRepository->criarEspacoCompartilhado([
            'nome' => trim($dados['nome']),
            'criado_por' => $idUsuario,
            'status' => StatusEspacoCompartilhado::Ativo,
        ]);

        $this->membroEspacoRepository->criarVinculoDeMembro([
            'id_espaco_compartilhado' => $espaco->id_espaco_compartilhado,
            'id_usuario' => $idUsuario,
            'papel' => PapelMembroEspaco::Administrador,
            'status' => StatusMembroEspaco::Ativo,
            'entrou_em' => now(),
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            $idUsuario,
            AcaoAuditoriaCompartilhada::EspacoCriado,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_ESPACO,
            (int) $espaco->id_espaco_compartilhado,
            null,
            ['nome' => $espaco->nome],
        );

        return $espaco;
    }

    public function atualizarEspacoCompartilhado(EspacoCompartilhado $espaco, int $idUsuario, array $dados): EspacoCompartilhado
    {
        $nomeAnterior = $espaco->nome;

        $atualizado = $this->espacoRepository->atualizarEspacoCompartilhado($espaco, [
            'nome' => trim($dados['nome']),
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            $idUsuario,
            AcaoAuditoriaCompartilhada::EspacoAtualizado,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_ESPACO,
            (int) $espaco->id_espaco_compartilhado,
            ['nome' => $nomeAnterior],
            ['nome' => $atualizado->nome],
        );

        return $atualizado;
    }

    public function arquivarEspacoCompartilhado(EspacoCompartilhado $espaco, ?int $idUsuario): EspacoCompartilhado
    {
        $arquivado = $this->espacoRepository->atualizarEspacoCompartilhado($espaco, [
            'status' => StatusEspacoCompartilhado::Arquivado,
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            $idUsuario,
            AcaoAuditoriaCompartilhada::EspacoArquivado,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_ESPACO,
            (int) $espaco->id_espaco_compartilhado,
            ['status' => StatusEspacoCompartilhado::Ativo->value],
            ['status' => StatusEspacoCompartilhado::Arquivado->value],
        );

        return $arquivado;
    }
}
