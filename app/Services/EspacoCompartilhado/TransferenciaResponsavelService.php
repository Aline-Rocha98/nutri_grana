<?php

namespace App\Services\EspacoCompartilhado;

use App\Enum\AcaoAuditoriaCompartilhada;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\Usuario\Usuario;
use App\Repositories\EspacoCompartilhado\LancamentoCompartilhadoRepository;
use Illuminate\Validation\ValidationException;

class TransferenciaResponsavelService
{
    public function __construct(
        private readonly BloqueioLancamentoCompartilhado $bloqueioLancamento,
        private readonly LancamentoCompartilhadoRepository $lancamentoCompartilhadoRepository,
        private readonly AuditoriaEspacoCompartilhadoService $auditoriaService,
        private readonly ValidacaoCategoriaResponsavelCompartilhado $validacaoCategoriaResponsavel,
    ) {}

    public function assumirLancamento(
        EspacoCompartilhado $espaco,
        LancamentoCompartilhado $lancamento,
        Usuario $usuario,
        int $versaoEsperada,
        mixed $idCategoria,
    ): LancamentoCompartilhado {
        $idUsuario = (int) $usuario->id_usuario;
        $bloqueado = $this->bloqueioLancamento->bloquearLancamentoNaVersaoEsperada($lancamento, $versaoEsperada);

        if (! $bloqueado->estaPendente()) {
            throw ValidationException::withMessages([
                'lancamento' => 'Somente lançamentos pendentes podem mudar de responsável. Se ele já foi pago, a responsável precisa estornar a baixa antes.',
            ]);
        }

        if ($bloqueado->ehResponsavel($idUsuario)) {
            throw ValidationException::withMessages([
                'lancamento' => 'Você já é responsável por este lançamento.',
            ]);
        }

        $idCategoriaValidada = $this->validacaoCategoriaResponsavel->garantirCategoriaPertenceAoResponsavel(
            $idUsuario,
            $idCategoria
        );

        $idResponsavelAnterior = $bloqueado->id_usuario_responsavel;

        $assumido = $this->lancamentoCompartilhadoRepository->atualizarLancamentoCompartilhado($bloqueado, [
            'id_usuario_responsavel' => $idUsuario,
            'id_categoria' => $idCategoriaValidada,
            'versao' => $bloqueado->versao + 1,
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            $idUsuario,
            AcaoAuditoriaCompartilhada::ResponsavelAlterado,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_LANCAMENTO,
            (int) $assumido->id_lancamento_compartilhado,
            ['descricao' => $bloqueado->descricao, 'id_usuario_responsavel' => $idResponsavelAnterior],
            ['descricao' => $assumido->descricao, 'id_usuario_responsavel' => $idUsuario, 'responsavel_nome' => $usuario->nome],
        );

        return $assumido;
    }
}
