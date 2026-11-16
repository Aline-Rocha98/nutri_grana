<?php

namespace App\Services\EspacoCompartilhado;

use App\Enum\AcaoAuditoriaCompartilhada;
use App\Enum\FormaPagamento;
use App\Enum\StatusLancamentoCompartilhado;
use App\Enum\TipoLancamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\Usuario\Usuario;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Repositories\EspacoCompartilhado\LancamentoCompartilhadoRepository;
use App\Services\Orcamento\VerificadorUltrapassagemOrcamento;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BaixaLancamentoCompartilhadoService
{
    public function __construct(
        private readonly BloqueioLancamentoCompartilhado $bloqueioLancamento,
        private readonly LancamentoCompartilhadoRepository $lancamentoCompartilhadoRepository,
        private readonly ContaBancariaRepository $contaBancariaRepository,
        private readonly VerificadorUltrapassagemOrcamento $verificadorUltrapassagemOrcamento,
        private readonly ValidacaoCategoriaResponsavelCompartilhado $validacaoCategoriaResponsavel,
        private readonly AuditoriaEspacoCompartilhadoService $auditoriaService,
    ) {}

    public function darBaixaNoLancamentoCompartilhado(
        EspacoCompartilhado $espaco,
        LancamentoCompartilhado $lancamento,
        Usuario $usuario,
        array $dadosPagamento,
    ): LancamentoCompartilhado {
        $idUsuario = (int) $usuario->id_usuario;
        $bloqueado = $this->bloqueioLancamento->bloquearLancamentoNaVersaoEsperada(
            $lancamento,
            (int) $dadosPagamento['versao']
        );

        $this->garantirQueUsuarioEhResponsavel($bloqueado, $idUsuario);

        if (! $bloqueado->estaPendente()) {
            throw ValidationException::withMessages([
                'lancamento' => 'Este lançamento já foi baixado ou cancelado.',
            ]);
        }

        $formaPagamento = FormaPagamento::from($dadosPagamento['forma_pagamento']);
        $this->garantirFormaPagamentoDoUsuario($idUsuario, $formaPagamento, $dadosPagamento);

        $idCategoria = $this->resolverCategoriaDaBaixa($bloqueado, $idUsuario, $dadosPagamento['id_categoria'] ?? null);

        $this->verificadorUltrapassagemOrcamento->garantirDentroDoLimiteOuConfirmado(
            $idUsuario,
            [
                'tipo' => TipoLancamento::Despesa->value,
                'valor' => (string) $bloqueado->valor,
                'data_vencimento' => $bloqueado->data_vencimento?->toDateString(),
                'id_categoria' => $idCategoria,
                'confirmar_ultrapassagem_orcamento' => $dadosPagamento['confirmar_ultrapassagem_orcamento'] ?? false,
            ],
            null,
            (int) $bloqueado->id_lancamento_compartilhado,
        );

        $retratoAnterior = $bloqueado->retratoParaAuditoria();
        $dataPagamento = $dadosPagamento['data_pagamento']
            ?? $dadosPagamento['data_vencimento']
            ?? Carbon::today()->toDateString();

        $baixado = $this->lancamentoCompartilhadoRepository->atualizarLancamentoCompartilhado($bloqueado, [
            'status' => StatusLancamentoCompartilhado::Pago,
            'data_pagamento' => $dataPagamento,
            'forma_pagamento' => $formaPagamento,
            'id_conta_bancaria' => $formaPagamento === FormaPagamento::ContaBancaria
                ? (int) $dadosPagamento['id_conta_bancaria']
                : null,
            'id_cartao_credito' => $formaPagamento === FormaPagamento::CartaoCredito
                ? (int) $dadosPagamento['id_cartao_credito']
                : null,
            'id_categoria' => $idCategoria,
            'id_usuario_baixa' => $idUsuario,
            'observacao' => $dadosPagamento['observacao'] ?? $bloqueado->observacao,
            'versao' => $bloqueado->versao + 1,
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            $idUsuario,
            AcaoAuditoriaCompartilhada::BaixaRealizada,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_LANCAMENTO,
            (int) $baixado->id_lancamento_compartilhado,
            $retratoAnterior,
            $baixado->retratoParaAuditoria(),
        );

        return $baixado;
    }

    public function estornarBaixaDoLancamento(
        EspacoCompartilhado $espaco,
        LancamentoCompartilhado $lancamento,
        Usuario $usuario,
        int $versaoEsperada,
    ): LancamentoCompartilhado {
        $idUsuario = (int) $usuario->id_usuario;
        $bloqueado = $this->bloqueioLancamento->bloquearLancamentoNaVersaoEsperada($lancamento, $versaoEsperada);

        $this->garantirQueUsuarioEhResponsavel($bloqueado, $idUsuario);

        if (! $bloqueado->estaPago()) {
            throw ValidationException::withMessages([
                'lancamento' => 'Somente lançamentos pagos podem ter a baixa estornada.',
            ]);
        }

        $retratoAnterior = $bloqueado->retratoParaAuditoria();

        $estornado = $this->lancamentoCompartilhadoRepository->atualizarLancamentoCompartilhado($bloqueado, [
            'status' => StatusLancamentoCompartilhado::Pendente,
            'data_pagamento' => null,
            'forma_pagamento' => null,
            'id_conta_bancaria' => null,
            'id_cartao_credito' => null,
            'id_usuario_baixa' => null,
            'versao' => $bloqueado->versao + 1,
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            $idUsuario,
            AcaoAuditoriaCompartilhada::BaixaEstornada,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_LANCAMENTO,
            (int) $estornado->id_lancamento_compartilhado,
            $retratoAnterior,
            $estornado->retratoParaAuditoria(),
        );

        return $estornado;
    }

    private function resolverCategoriaDaBaixa(
        LancamentoCompartilhado $lancamento,
        int $idUsuario,
        mixed $idCategoriaInformada,
    ): int {
        $idCategoria = $idCategoriaInformada ?: $lancamento->id_categoria;

        return $this->validacaoCategoriaResponsavel->garantirCategoriaPertenceAoResponsavel(
            $idUsuario,
            $idCategoria
        );
    }

    private function garantirFormaPagamentoDoUsuario(
        int $idUsuario,
        FormaPagamento $forma,
        array $dados,
    ): void {
        if ($forma === FormaPagamento::ContaBancaria) {
            if (empty($dados['id_conta_bancaria'])) {
                throw ValidationException::withMessages([
                    'id_conta_bancaria' => 'Selecione a conta bancária.',
                ]);
            }

            $conta = $this->contaBancariaRepository->buscarParaUsuario(
                (int) $dados['id_conta_bancaria'],
                $idUsuario
            );

            if (! $conta) {
                throw ValidationException::withMessages([
                    'id_conta_bancaria' => 'Conta bancária inválida.',
                ]);
            }

            return;
        }

        if ($forma === FormaPagamento::CartaoCredito) {
            if (empty($dados['id_cartao_credito'])) {
                throw ValidationException::withMessages([
                    'id_cartao_credito' => 'Selecione o cartão de crédito.',
                ]);
            }

            $cartao = CartaoCredito::query()
                ->where('id_cartao_credito', $dados['id_cartao_credito'])
                ->where('id_usuario', $idUsuario)
                ->first();

            if (! $cartao) {
                throw ValidationException::withMessages([
                    'id_cartao_credito' => 'Cartão de crédito inválido.',
                ]);
            }
        }
    }

    private function garantirQueUsuarioEhResponsavel(LancamentoCompartilhado $lancamento, int $idUsuario): void
    {
        if (! $lancamento->ehResponsavel($idUsuario)) {
            throw new AuthorizationException('Este lançamento pertence a outro membro. Para dar baixa, transfira a responsabilidade para você.');
        }
    }
}
