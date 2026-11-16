<?php

namespace App\Services\EspacoCompartilhado;

use App\Http\Resources\EspacoCompartilhado\ConviteRecebidoResource;
use App\Models\Usuario\Usuario;
use Illuminate\Contracts\Session\Session;

class AvisoConvitesPendentesService
{
    public const CHAVE_SESSAO_VERIFICAR_CONVITES = 'verificar_convites_pendentes';

    public function __construct(
        private readonly ConviteCompartilhamentoService $conviteService,
    ) {}

    public function marcarVerificacaoDeConvitesAposLogin(Usuario $usuario, Session $sessao): void
    {
        if (! $usuario->possuiCompartilhamentoHabilitado()) {
            return;
        }

        $sessao->put(self::CHAVE_SESSAO_VERIFICAR_CONVITES, true);
    }

    public function consumirConvitesPendentesParaAviso(Usuario $usuario, Session $sessao): array
    {
        if (! $sessao->pull(self::CHAVE_SESSAO_VERIFICAR_CONVITES, false)) {
            return [];
        }

        if (! $usuario->possuiCompartilhamentoHabilitado()) {
            return [];
        }

        return ConviteRecebidoResource::collection(
            $this->conviteService->listarConvitesRecebidosPeloUsuario($usuario)
        )->resolve();
    }
}
