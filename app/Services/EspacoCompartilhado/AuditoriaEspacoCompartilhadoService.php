<?php

namespace App\Services\EspacoCompartilhado;

use App\Enum\AcaoAuditoriaCompartilhada;
use App\Enum\SecurityEvent;
use App\Repositories\EspacoCompartilhado\AuditoriaEspacoCompartilhadoRepository;
use App\Support\Security\SecurityAuditor;
use Illuminate\Support\Collection;

class AuditoriaEspacoCompartilhadoService
{
    public const ENTIDADE_ESPACO = 'espaco';

    public const ENTIDADE_MEMBRO = 'membro';

    public const ENTIDADE_CONVITE = 'convite';

    public const ENTIDADE_LANCAMENTO = 'lancamento';

    public const ENTIDADE_RECORRENCIA = 'recorrencia';

    private const LIMITE_HISTORICO = 50;

    public function __construct(
        private readonly AuditoriaEspacoCompartilhadoRepository $auditoriaRepository,
    ) {}

    public function registrarAcaoNoEspaco(
        int $idEspaco,
        ?int $idUsuario,
        AcaoAuditoriaCompartilhada $acao,
        string $entidade,
        ?int $idEntidade = null,
        ?array $dadosAnteriores = null,
        ?array $dadosNovos = null,
    ): void {
        $this->auditoriaRepository->registrarAuditoria([
            'id_espaco_compartilhado' => $idEspaco,
            'id_usuario' => $idUsuario,
            'acao' => $acao,
            'entidade' => $entidade,
            'id_entidade' => $idEntidade,
            'dados_anteriores' => $dadosAnteriores,
            'dados_novos' => $dadosNovos,
            'ip' => request()?->ip(),
        ]);

        SecurityAuditor::log(SecurityEvent::SharedSpaceActivity, [
            'id_usuario' => $idUsuario,
            'id_espaco_compartilhado' => $idEspaco,
            'acao' => $acao->value,
            'entidade' => $entidade,
            'id_entidade' => $idEntidade,
        ]);
    }

    public function listarHistoricoDoEspaco(int $idEspaco): Collection
    {
        return $this->auditoriaRepository->listarHistoricoRecenteDoEspaco($idEspaco, self::LIMITE_HISTORICO);
    }
}
