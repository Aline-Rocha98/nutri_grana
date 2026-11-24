<?php

namespace App\Policies\EspacoCompartilhado;

use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\Usuario\Usuario;
use App\Repositories\EspacoCompartilhado\MembroEspacoCompartilhadoRepository;
use Illuminate\Auth\Access\Response;

class LancamentoCompartilhadoPolicy
{
    public function __construct(
        private readonly MembroEspacoCompartilhadoRepository $membroEspacoRepository,
    ) {}

    public function create(Usuario $usuario, EspacoCompartilhado $espaco): bool
    {
        return $espaco->estaAtivo()
            && $this->membroEspacoRepository->usuarioEhMembroAtivoDoEspaco(
                (int) $espaco->id_espaco_compartilhado,
                (int) $usuario->getAuthIdentifier()
            );
    }

    public function view(Usuario $usuario, LancamentoCompartilhado $lancamento): bool
    {
        return $this->ehMembroAtivoDoEspacoDoLancamento($usuario, $lancamento);
    }

    public function update(Usuario $usuario, LancamentoCompartilhado $lancamento): bool
    {
        if (! $this->ehMembroAtivoDoEspacoDoLancamento($usuario, $lancamento)) {
            return false;
        }

        $idUsuario = (int) $usuario->getAuthIdentifier();

        return (int) $lancamento->criado_por === $idUsuario
            || $lancamento->ehResponsavel($idUsuario)
            || $this->membroEspacoRepository->usuarioEhAdministradorAtivoDoEspaco(
                (int) $lancamento->id_espaco_compartilhado,
                $idUsuario
            );
    }

    public function delete(Usuario $usuario, LancamentoCompartilhado $lancamento): bool
    {
        return $this->update($usuario, $lancamento);
    }

    public function assumirResponsabilidade(Usuario $usuario, LancamentoCompartilhado $lancamento): bool
    {
        return $this->ehMembroAtivoDoEspacoDoLancamento($usuario, $lancamento);
    }

    public function darBaixa(Usuario $usuario, LancamentoCompartilhado $lancamento): Response
    {
        if (! $this->ehMembroAtivoDoEspacoDoLancamento($usuario, $lancamento)) {
            return Response::deny('Você não participa deste espaço.');
        }

        if (! $lancamento->ehResponsavel((int) $usuario->getAuthIdentifier())) {
            return Response::deny($this->montarMensagemLancamentoDeOutroMembro($lancamento));
        }

        return Response::allow();
    }

    public function estornarBaixa(Usuario $usuario, LancamentoCompartilhado $lancamento): Response
    {
        if (! $this->ehMembroAtivoDoEspacoDoLancamento($usuario, $lancamento)) {
            return Response::deny('Você não participa deste espaço.');
        }

        if (! $lancamento->ehResponsavel((int) $usuario->getAuthIdentifier())) {
            return Response::deny('Somente quem é responsável pelo lançamento pode estornar a baixa.');
        }

        return Response::allow();
    }

    private function ehMembroAtivoDoEspacoDoLancamento(Usuario $usuario, LancamentoCompartilhado $lancamento): bool
    {
        return $this->membroEspacoRepository->usuarioEhMembroAtivoDoEspaco(
            (int) $lancamento->id_espaco_compartilhado,
            (int) $usuario->getAuthIdentifier()
        );
    }

    private function montarMensagemLancamentoDeOutroMembro(LancamentoCompartilhado $lancamento): string
    {
        $lancamento->loadMissing('responsavel:id_usuario,nome');
        $nomeResponsavel = $lancamento->responsavel?->nome;

        if (! $nomeResponsavel) {
            return 'Este lançamento está sem responsável. Para dar baixa, transfira a responsabilidade para você.';
        }

        return "Este lançamento pertence a {$nomeResponsavel}. Para dar baixa, transfira a responsabilidade para você.";
    }
}
