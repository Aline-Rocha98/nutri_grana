<?php

namespace App\Services\EspacoCompartilhado;

use App\Enum\AcaoAuditoriaCompartilhada;
use App\Enum\StatusLancamentoCompartilhado;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Repositories\EspacoCompartilhado\LancamentoCompartilhadoRepository;
use App\Repositories\EspacoCompartilhado\MembroEspacoCompartilhadoRepository;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LancamentoCompartilhadoService
{
    public function __construct(
        private readonly LancamentoCompartilhadoRepository $lancamentoCompartilhadoRepository,
        private readonly MembroEspacoCompartilhadoRepository $membroEspacoRepository,
        private readonly RecorrenciaCompartilhadaService $recorrenciaService,
        private readonly BloqueioLancamentoCompartilhado $bloqueioLancamento,
        private readonly AuditoriaEspacoCompartilhadoService $auditoriaService,
        private readonly ValidacaoCategoriaResponsavelCompartilhado $validacaoCategoriaResponsavel,
    ) {}

    public function listarLancamentosDoMes(EspacoCompartilhado $espaco, int $ano, int $mes, ?string $status = null): Collection
    {
        $idEspaco = (int) $espaco->id_espaco_compartilhado;

        $this->recorrenciaService->gerarOcorrenciasAteOFimDoMes($idEspaco, $ano, $mes);

        return $this->lancamentoCompartilhadoRepository
            ->listarLancamentosDoMes($idEspaco, $ano, $mes, $status)
            ->each(fn (LancamentoCompartilhado $lancamento) => $lancamento->setRelation('espaco', $espaco));
    }

    public function criarLancamentoCompartilhado(EspacoCompartilhado $espaco, int $idUsuario, array $dados): LancamentoCompartilhado
    {
        $this->garantirResponsavelEhMembroAtivo($espaco, $dados['id_usuario_responsavel']);
        $idCategoria = $this->validacaoCategoriaResponsavel->garantirCategoriaPertenceAoResponsavel(
            (int) $dados['id_usuario_responsavel'],
            $dados['id_categoria'] ?? null
        );
        $dados['id_categoria'] = $idCategoria;

        if (($dados['recorrente'] ?? false) === true) {
            $primeiraOcorrencia = $this->recorrenciaService->criarRecorrenciaCompartilhada($espaco, $idUsuario, $dados);
            $this->registrarAuditoriaDoLancamento($primeiraOcorrencia, $idUsuario, AcaoAuditoriaCompartilhada::LancamentoCriado, null);

            return $primeiraOcorrencia;
        }

        $lancamento = $this->lancamentoCompartilhadoRepository->criarLancamentoCompartilhado([
            'id_espaco_compartilhado' => (int) $espaco->id_espaco_compartilhado,
            'id_usuario_responsavel' => (int) $dados['id_usuario_responsavel'],
            'id_categoria' => $idCategoria,
            'descricao' => $dados['descricao'],
            'valor' => $dados['valor'],
            'data_vencimento' => $dados['data_vencimento'],
            'status' => StatusLancamentoCompartilhado::Pendente,
            'versao' => 1,
            'observacao' => $dados['observacao'] ?? null,
            'criado_por' => $idUsuario,
        ]);

        $this->registrarAuditoriaDoLancamento($lancamento, $idUsuario, AcaoAuditoriaCompartilhada::LancamentoCriado, null);

        return $lancamento;
    }

    public function atualizarLancamentoCompartilhado(
        EspacoCompartilhado $espaco,
        LancamentoCompartilhado $lancamento,
        int $idUsuario,
        array $dados,
    ): LancamentoCompartilhado {
        $bloqueado = $this->bloqueioLancamento->bloquearLancamentoNaVersaoEsperada($lancamento, (int) $dados['versao']);

        if (! $bloqueado->estaPendente()) {
            throw ValidationException::withMessages([
                'lancamento' => 'Lançamentos pagos ou cancelados não podem ser editados. Estorne a baixa antes de alterar.',
            ]);
        }

        $this->garantirResponsavelEhMembroAtivo($espaco, $dados['id_usuario_responsavel']);
        $idCategoria = $this->validacaoCategoriaResponsavel->garantirCategoriaPertenceAoResponsavel(
            (int) $dados['id_usuario_responsavel'],
            $dados['id_categoria'] ?? null
        );

        $retratoAnterior = $bloqueado->retratoParaAuditoria();
        $idResponsavelAnterior = $bloqueado->id_usuario_responsavel;

        $atualizado = $this->lancamentoCompartilhadoRepository->atualizarLancamentoCompartilhado($bloqueado, [
            'descricao' => $dados['descricao'],
            'valor' => $dados['valor'],
            'data_vencimento' => $dados['data_vencimento'],
            'id_usuario_responsavel' => (int) $dados['id_usuario_responsavel'],
            'id_categoria' => $idCategoria,
            'observacao' => $dados['observacao'] ?? null,
            'versao' => $bloqueado->versao + 1,
        ]);

        $this->registrarAuditoriaDoLancamento($atualizado, $idUsuario, AcaoAuditoriaCompartilhada::LancamentoAtualizado, $retratoAnterior);

        if ((int) $idResponsavelAnterior !== (int) $atualizado->id_usuario_responsavel) {
            $atualizado->load('responsavel:id_usuario,nome');

            $this->auditoriaService->registrarAcaoNoEspaco(
                (int) $atualizado->id_espaco_compartilhado,
                $idUsuario,
                AcaoAuditoriaCompartilhada::ResponsavelAlterado,
                AuditoriaEspacoCompartilhadoService::ENTIDADE_LANCAMENTO,
                (int) $atualizado->id_lancamento_compartilhado,
                ['descricao' => $retratoAnterior['descricao'], 'id_usuario_responsavel' => $idResponsavelAnterior],
                [
                    'descricao' => $atualizado->descricao,
                    'id_usuario_responsavel' => $atualizado->id_usuario_responsavel,
                    'responsavel_nome' => $atualizado->responsavel?->nome,
                ],
            );
        }

        if (($dados['aplicar_na_recorrencia'] ?? false) === true) {
            $this->recorrenciaService->atualizarModeloDaRecorrencia($atualizado, $idUsuario);
        }

        return $atualizado;
    }

    public function excluirLancamentoCompartilhado(
        LancamentoCompartilhado $lancamento,
        int $idUsuario,
        bool $encerrarRecorrencia = false,
    ): void {
        $bloqueado = $this->bloqueioLancamento->bloquearLancamentoNaVersaoEsperada($lancamento, null);

        if ($bloqueado->estaPago()) {
            throw ValidationException::withMessages([
                'lancamento' => 'Este lançamento já foi pago. Estorne a baixa antes de excluir.',
            ]);
        }

        $retratoAnterior = $bloqueado->retratoParaAuditoria();

        if ($bloqueado->pertenceARecorrencia()) {
            $cancelado = $this->lancamentoCompartilhadoRepository->atualizarLancamentoCompartilhado($bloqueado, [
                'status' => StatusLancamentoCompartilhado::Cancelado,
                'versao' => $bloqueado->versao + 1,
            ]);

            $this->registrarAuditoriaDoLancamento($cancelado, $idUsuario, AcaoAuditoriaCompartilhada::LancamentoCancelado, $retratoAnterior);

            if ($encerrarRecorrencia) {
                $this->recorrenciaService->encerrarRecorrenciaAPartirDaOcorrencia($cancelado, $idUsuario);
            }

            return;
        }

        $idLancamento = (int) $bloqueado->id_lancamento_compartilhado;
        $idEspaco = (int) $bloqueado->id_espaco_compartilhado;

        $this->lancamentoCompartilhadoRepository->excluirLancamentoCompartilhado($bloqueado);

        $this->auditoriaService->registrarAcaoNoEspaco(
            $idEspaco,
            $idUsuario,
            AcaoAuditoriaCompartilhada::LancamentoExcluido,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_LANCAMENTO,
            $idLancamento,
            $retratoAnterior,
            null,
        );
    }

    private function garantirResponsavelEhMembroAtivo(EspacoCompartilhado $espaco, mixed $idResponsavel): void
    {
        $responsavelValido = $idResponsavel !== null
            && $this->membroEspacoRepository->usuarioEhMembroAtivoDoEspaco(
                (int) $espaco->id_espaco_compartilhado,
                (int) $idResponsavel
            );

        if (! $responsavelValido) {
            throw ValidationException::withMessages([
                'id_usuario_responsavel' => 'Selecione um membro ativo do espaço como responsável.',
            ]);
        }
    }

    private function registrarAuditoriaDoLancamento(
        LancamentoCompartilhado $lancamento,
        int $idUsuario,
        AcaoAuditoriaCompartilhada $acao,
        ?array $retratoAnterior,
    ): void {
        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $lancamento->id_espaco_compartilhado,
            $idUsuario,
            $acao,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_LANCAMENTO,
            (int) $lancamento->id_lancamento_compartilhado,
            $retratoAnterior,
            $lancamento->retratoParaAuditoria(),
        );
    }
}
