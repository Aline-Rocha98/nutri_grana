<?php

namespace App\Services\EspacoCompartilhado;

use App\Enum\AcaoAuditoriaCompartilhada;
use App\Enum\PapelMembroEspaco;
use App\Enum\StatusMembroEspaco;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\MembroEspacoCompartilhado;
use App\Repositories\EspacoCompartilhado\MembroEspacoCompartilhadoRepository;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MembroEspacoCompartilhadoService
{
    public function __construct(
        private readonly MembroEspacoCompartilhadoRepository $membroEspacoRepository,
        private readonly EspacoCompartilhadoService $espacoService,
        private readonly AuditoriaEspacoCompartilhadoService $auditoriaService,
    ) {}

    public function listarMembrosAtivosDoEspaco(EspacoCompartilhado $espaco): Collection
    {
        return $this->membroEspacoRepository
            ->listarMembrosAtivosDoEspaco((int) $espaco->id_espaco_compartilhado)
            ->each(fn (MembroEspacoCompartilhado $membro) => $membro->setRelation('espaco', $espaco));
    }

    public function usuarioEhAdministradorDoEspaco(EspacoCompartilhado $espaco, int $idUsuario): bool
    {
        return $this->membroEspacoRepository->usuarioEhAdministradorAtivoDoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            $idUsuario
        );
    }

    public function adicionarMembroAoEspaco(
        EspacoCompartilhado $espaco,
        int $idUsuarioNovoMembro,
        PapelMembroEspaco $papel = PapelMembroEspaco::Membro,
    ): MembroEspacoCompartilhado {
        $idEspaco = (int) $espaco->id_espaco_compartilhado;
        $vinculoExistente = $this->membroEspacoRepository->buscarVinculoDoUsuarioNoEspaco($idEspaco, $idUsuarioNovoMembro);

        if ($vinculoExistente?->estaAtivo()) {
            throw ValidationException::withMessages([
                'convite' => 'Você já participa deste espaço compartilhado.',
            ]);
        }

        $dadosDoVinculo = [
            'papel' => $papel,
            'status' => StatusMembroEspaco::Ativo,
            'entrou_em' => now(),
        ];

        $membro = $vinculoExistente
            ? $this->membroEspacoRepository->atualizarVinculoDeMembro($vinculoExistente, $dadosDoVinculo)
            : $this->membroEspacoRepository->criarVinculoDeMembro(array_merge($dadosDoVinculo, [
                'id_espaco_compartilhado' => $idEspaco,
                'id_usuario' => $idUsuarioNovoMembro,
            ]));

        $this->auditoriaService->registrarAcaoNoEspaco(
            $idEspaco,
            $idUsuarioNovoMembro,
            AcaoAuditoriaCompartilhada::MembroEntrou,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_MEMBRO,
            (int) $membro->id_membro_espaco,
            null,
            ['id_usuario' => $idUsuarioNovoMembro, 'papel' => $papel->value],
        );

        return $membro;
    }

    public function removerMembroDoEspaco(EspacoCompartilhado $espaco, MembroEspacoCompartilhado $membro, int $idUsuarioResponsavelAcao): void
    {
        if ((int) $membro->id_usuario === $idUsuarioResponsavelAcao) {
            throw ValidationException::withMessages([
                'membro' => 'Para sair do espaço, use a opção "Sair do espaço".',
            ]);
        }

        if (! $membro->estaAtivo()) {
            throw ValidationException::withMessages([
                'membro' => 'Este membro não está mais ativo no espaço.',
            ]);
        }

        $membro->loadMissing('usuario:id_usuario,nome');
        $nomeDoMembro = $membro->usuario?->nome;

        $this->membroEspacoRepository->atualizarVinculoDeMembro($membro, [
            'status' => StatusMembroEspaco::Removido,
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            $idUsuarioResponsavelAcao,
            AcaoAuditoriaCompartilhada::MembroRemovido,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_MEMBRO,
            (int) $membro->id_membro_espaco,
            ['id_usuario' => (int) $membro->id_usuario, 'status' => StatusMembroEspaco::Ativo->value],
            ['id_usuario' => (int) $membro->id_usuario, 'nome' => $nomeDoMembro, 'status' => StatusMembroEspaco::Removido->value],
        );
    }

    public function sairDoEspaco(EspacoCompartilhado $espaco, int $idUsuario): void
    {
        $idEspaco = (int) $espaco->id_espaco_compartilhado;
        $vinculo = $this->membroEspacoRepository->buscarVinculoAtivoDoUsuarioNoEspaco($idEspaco, $idUsuario);

        if (! $vinculo) {
            throw ValidationException::withMessages([
                'membro' => 'Você não participa deste espaço.',
            ]);
        }

        $this->registrarSaidaDoMembro($espaco, $vinculo);
    }

    public function tratarEspacosNaExclusaoDaConta(int $idUsuario): void
    {
        $vinculosAtivos = $this->membroEspacoRepository->listarVinculosAtivosDoUsuario($idUsuario);

        foreach ($vinculosAtivos as $vinculo) {
            $espaco = $vinculo->espaco;

            if (! $espaco) {
                continue;
            }

            if ($vinculo->ehAdministrador()) {
                $this->promoverNovoAdministradorSeNecessario($espaco, $idUsuario);
            }

            $this->registrarSaidaDoMembro($espaco, $vinculo);
        }
    }

    private function registrarSaidaDoMembro(EspacoCompartilhado $espaco, MembroEspacoCompartilhado $vinculo): void
    {
        $idEspaco = (int) $espaco->id_espaco_compartilhado;

        $this->membroEspacoRepository->atualizarVinculoDeMembro($vinculo, [
            'status' => StatusMembroEspaco::Saiu,
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            $idEspaco,
            (int) $vinculo->id_usuario,
            AcaoAuditoriaCompartilhada::MembroSaiu,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_MEMBRO,
            (int) $vinculo->id_membro_espaco,
            ['status' => StatusMembroEspaco::Ativo->value],
            ['status' => StatusMembroEspaco::Saiu->value],
        );

        if ($espaco->estaAtivo() && $this->membroEspacoRepository->contarMembrosAtivosDoEspaco($idEspaco) === 0) {
            $this->espacoService->arquivarEspacoCompartilhado($espaco, (int) $vinculo->id_usuario);
        }
    }

    private function promoverNovoAdministradorSeNecessario(EspacoCompartilhado $espaco, int $idUsuarioQueSai): void
    {
        $idEspaco = (int) $espaco->id_espaco_compartilhado;

        if ($this->membroEspacoRepository->contarAdministradoresAtivosDoEspaco($idEspaco) > 1) {
            return;
        }

        $proximoAdministrador = $this->membroEspacoRepository->buscarMembroAtivoMaisAntigoDoEspaco($idEspaco, $idUsuarioQueSai);

        if (! $proximoAdministrador) {
            return;
        }

        $this->membroEspacoRepository->atualizarVinculoDeMembro($proximoAdministrador, [
            'papel' => PapelMembroEspaco::Administrador,
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            $idEspaco,
            $idUsuarioQueSai,
            AcaoAuditoriaCompartilhada::AdministradorPromovido,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_MEMBRO,
            (int) $proximoAdministrador->id_membro_espaco,
            ['papel' => PapelMembroEspaco::Membro->value],
            [
                'id_usuario' => (int) $proximoAdministrador->id_usuario,
                'nome' => $proximoAdministrador->loadMissing('usuario:id_usuario,nome')->usuario?->nome,
                'papel' => PapelMembroEspaco::Administrador->value,
            ],
        );
    }
}
