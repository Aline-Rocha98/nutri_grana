<?php

namespace App\Services\EspacoCompartilhado;

use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Repositories\EspacoCompartilhado\LancamentoCompartilhadoRepository;
use Illuminate\Validation\ValidationException;

class BloqueioLancamentoCompartilhado
{
    public function __construct(
        private readonly LancamentoCompartilhadoRepository $lancamentoCompartilhadoRepository,
    ) {}

    public function bloquearLancamentoNaVersaoEsperada(LancamentoCompartilhado $lancamento, ?int $versaoEsperada): LancamentoCompartilhado
    {
        $bloqueado = $this->lancamentoCompartilhadoRepository->bloquearLancamentoParaAtualizacao(
            (int) $lancamento->id_lancamento_compartilhado
        );

        if (! $bloqueado) {
            throw ValidationException::withMessages([
                'lancamento' => 'Este lançamento não existe mais. Recarregue a página.',
            ]);
        }

        if ($versaoEsperada !== null && (int) $bloqueado->versao !== $versaoEsperada) {
            throw ValidationException::withMessages([
                'versao' => 'Este lançamento foi alterado por outro membro. Recarregue a página e tente novamente.',
            ]);
        }

        return $bloqueado;
    }
}
