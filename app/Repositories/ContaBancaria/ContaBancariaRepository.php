<?php

namespace App\Repositories\ContaBancaria;

use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use Illuminate\Support\Collection;

class ContaBancariaRepository
{
    public function __construct(
        private readonly MovimentacaoSaldoGuardadoRepository $movimentacaoSaldoGuardadoRepository,
    ) {}

    public function listarPorUsuario(int $idUsuario): Collection
    {
        $contas = ContaBancaria::query()
            ->where('id_usuario', $idUsuario)
            ->orderBy('arquivada')
            ->orderBy('nome')
            ->get();

        return $contas->each(function (ContaBancaria $conta): void {
            $this->preencherSaldoAtual($conta);
            $conta->setAttribute('total_lancamentos', $this->contarLancamentos((int) $conta->id_conta_bancaria));
        });
    }

    public function listarParaDashboard(int $idUsuario): Collection
    {
        $contas = ContaBancaria::query()
            ->where('id_usuario', $idUsuario)
            ->where('arquivada', SimNao::Nao)
            ->where('exibir_resumo', SimNao::Sim)
            ->orderBy('nome')
            ->get();

        return $contas->each(fn (ContaBancaria $conta) => $this->preencherSaldoAtual($conta));
    }

    public function listarOpcoesAtivasDoUsuario(int $idUsuario): Collection
    {
        return ContaBancaria::query()
            ->where('id_usuario', $idUsuario)
            ->where('arquivada', SimNao::Nao)
            ->orderBy('nome')
            ->get(['id_conta_bancaria', 'nome']);
    }

    public function listarContasDoUsuarioPorIds(int $idUsuario, array $idsContasBancarias): Collection
    {
        if ($idsContasBancarias === []) {
            return collect();
        }

        return ContaBancaria::query()
            ->where('id_usuario', $idUsuario)
            ->whereIn('id_conta_bancaria', $idsContasBancarias)
            ->get(['id_conta_bancaria', 'nome', 'arquivada'])
            ->keyBy('id_conta_bancaria');
    }

    public function listarMovimentacoesRecentesDaConta(int $idContaBancaria, int $limite): Collection
    {
        return Lancamento::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->where('eh_recorrencia', SimNao::Nao)
            ->where('situacao', '!=', SituacaoLancamento::Cancelado)
            ->with('categoria')
            ->orderByDesc('data_vencimento')
            ->orderByDesc('id_lancamento')
            ->limit($limite)
            ->get();
    }

    public function buscarParaUsuario(int $id, int $idUsuario): ?ContaBancaria
    {
        return ContaBancaria::query()
            ->where('id_conta_bancaria', $id)
            ->where('id_usuario', $idUsuario)
            ->first();
    }

    public function limparPadraoDescontoDoUsuario(int $idUsuario, ?int $excetoId = null): void
    {
        ContaBancaria::query()
            ->where('id_usuario', $idUsuario)
            ->when($excetoId !== null, fn ($query) => $query->where('id_conta_bancaria', '!=', $excetoId))
            ->where('padrao_desconto', 'S')
            ->update(['padrao_desconto' => 'N']);
    }

    public function criar(array $dados): ContaBancaria
    {
        return ContaBancaria::query()->create($dados);
    }

    public function atualizar(ContaBancaria $contaBancaria, array $dados): ContaBancaria
    {
        $contaBancaria->update($dados);

        return $contaBancaria->refresh();
    }

    public function excluir(ContaBancaria $contaBancaria): void
    {
        $contaBancaria->delete();
    }

    public function temLancamentos(ContaBancaria $contaBancaria): bool
    {
        $idContaBancaria = (int) $contaBancaria->id_conta_bancaria;

        return $this->contarLancamentos($idContaBancaria) > 0
            || $this->movimentacaoSaldoGuardadoRepository->contemMovimentacoesDaConta($idContaBancaria);
    }

    public function contarLancamentos(int $idContaBancaria): int
    {
        return Lancamento::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->count();
    }

    public function saldoMovimentado(int $idContaBancaria): float
    {
        $receitas = (float) Lancamento::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->where('tipo', TipoLancamento::Receita)
            ->whereIn('situacao', [
                SituacaoLancamento::Pago,
                SituacaoLancamento::Recebido,
            ])
            ->where('eh_recorrencia', SimNao::Nao)
            ->sum('valor');

        $despesas = (float) Lancamento::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->where('tipo', TipoLancamento::Despesa)
            ->whereIn('situacao', [
                SituacaoLancamento::Pago,
                SituacaoLancamento::Recebido,
            ])
            ->where('eh_recorrencia', SimNao::Nao)
            ->sum('valor');

        return $receitas - $despesas;
    }

    public function saldoTotalDaConta(ContaBancaria $conta): float
    {
        $idContaBancaria = (int) $conta->id_conta_bancaria;
        $movimentado = $this->saldoMovimentado($idContaBancaria);
        $separacoesRecebidas = $this->movimentacaoSaldoGuardadoRepository->somarSeparacoesComDestinoNaConta($idContaBancaria);
        $separacoesEnviadas = $this->movimentacaoSaldoGuardadoRepository->somarSeparacoesComOrigemNaConta($idContaBancaria);
        $resgatesRecebidos = $this->movimentacaoSaldoGuardadoRepository->somarResgatesRecebidosNaConta($idContaBancaria);
        $resgatesEnviados = $this->movimentacaoSaldoGuardadoRepository->somarResgatesEnviadosDaConta($idContaBancaria);

        return round(
            (float) $conta->saldo_inicial
            + $movimentado
            + $separacoesRecebidas
            - $separacoesEnviadas
            + $resgatesRecebidos
            - $resgatesEnviados,
            2,
        );
    }

    public function saldoGuardadoDaConta(ContaBancaria $conta): float
    {
        return $this->movimentacaoSaldoGuardadoRepository->somarSaldoGuardadoDaConta((int) $conta->id_conta_bancaria);
    }

    public function saldoDisponivelDaConta(ContaBancaria $conta): float
    {
        return round($this->saldoTotalDaConta($conta) - $this->saldoGuardadoDaConta($conta), 2);
    }

    private function preencherSaldoAtual(ContaBancaria $conta): void
    {
        $saldoTotal = $this->saldoTotalDaConta($conta);
        $saldoGuardado = $this->saldoGuardadoDaConta($conta);

        $conta->setAttribute('saldo_atual', $saldoTotal);
        $conta->setAttribute('saldo_guardado', $saldoGuardado);
        $conta->setAttribute('saldo_disponivel', round($saldoTotal - $saldoGuardado, 2));
    }
}
