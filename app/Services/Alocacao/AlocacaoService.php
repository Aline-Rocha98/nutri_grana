<?php

namespace App\Services\Alocacao;

use App\Enum\SimNao;
use App\Enum\TipoMovimentacaoSaldoGuardado;
use App\Models\Alocacao\Alocacao;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\ContaBancaria\MovimentacaoSaldoGuardado;
use App\Repositories\Alocacao\AlocacaoRepository;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Repositories\ContaBancaria\MovimentacaoSaldoGuardadoRepository;
use App\Support\Dashboard\DashboardCache;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AlocacaoService
{
    private const ALOCACOES_CADASTRADAS_POR_PAGINA = 8;

    public const NOME_PAGINA_ALOCACOES_CADASTRADAS = 'pagina_alocacoes';

    private const LIMITE_MOVIMENTACOES_RECENTES = 5;

    public function __construct(
        private readonly AlocacaoRepository $alocacaoRepository,
        private readonly MovimentacaoSaldoGuardadoRepository $movimentacaoSaldoGuardadoRepository,
        private readonly ContaBancariaRepository $contaBancariaRepository,
        private readonly ConsolidadorSaldoAlocacao $consolidadorSaldoAlocacao,
    ) {}

    public function listarAlocacoesComSaldoDoUsuario(int $idUsuario): Collection
    {
        $alocacoes = $this->alocacaoRepository->listarAlocacoesDoUsuario($idUsuario);
        $idsAlocacoes = $alocacoes->pluck('id_alocacao')->map(fn ($id) => (int) $id)->all();

        $saldosPorConta = $this->consolidadorSaldoAlocacao->calcularSaldosPorContaDasAlocacoes($idsAlocacoes);
        $movimentacoesRecentes = $this->movimentacaoSaldoGuardadoRepository->listarMovimentacoesRecentesDasAlocacoes(
            $idsAlocacoes,
            self::LIMITE_MOVIMENTACOES_RECENTES,
        );

        $idsContas = collect($saldosPorConta)->flatMap(fn (array $saldos) => array_keys($saldos))->unique()->values()->all();
        $contasPorId = $this->contaBancariaRepository->listarContasDoUsuarioPorIds($idUsuario, $idsContas);

        return $alocacoes->each(function (Alocacao $alocacao) use ($saldosPorConta, $contasPorId, $movimentacoesRecentes): void {
            $idAlocacao = (int) $alocacao->id_alocacao;

            $alocacao->setAttribute('saldos_por_conta', $this->montarSaldosPorConta($saldosPorConta[$idAlocacao] ?? [], $contasPorId));
            $alocacao->setAttribute('movimentacoes_recentes', $movimentacoesRecentes[$idAlocacao] ?? collect());
        });
    }

    public function paginarAlocacoesCadastradas(int $idUsuario): LengthAwarePaginator
    {
        return $this->alocacaoRepository->paginarAlocacoesCadastradas(
            $idUsuario,
            self::ALOCACOES_CADASTRADAS_POR_PAGINA,
            self::NOME_PAGINA_ALOCACOES_CADASTRADAS,
        );
    }

    public function listarOpcoesDeAlocacoesDoUsuario(int $idUsuario): Collection
    {
        return $this->alocacaoRepository->listarOpcoesDoUsuario($idUsuario);
    }

    public function criarAlocacao(int $idUsuario, string $nome): Alocacao
    {
        $nomeNormalizado = $this->normalizarNomeAlocacao($nome);
        $this->garantirNomeDisponivel($idUsuario, $nomeNormalizado);

        return $this->alocacaoRepository->criar([
            'id_usuario' => $idUsuario,
            'nome' => $nomeNormalizado,
            'saldo_consolidado' => 0,
        ]);
    }

    public function renomearAlocacao(Alocacao $alocacao, int $idUsuario, string $nome): Alocacao
    {
        $this->garantirPropriedade($alocacao, $idUsuario);

        $nomeNormalizado = $this->normalizarNomeAlocacao($nome);
        $this->garantirNomeDisponivel($idUsuario, $nomeNormalizado, (int) $alocacao->id_alocacao);

        return $this->alocacaoRepository->atualizar($alocacao, ['nome' => $nomeNormalizado]);
    }

    public function excluirAlocacao(Alocacao $alocacao, int $idUsuario): void
    {
        $this->garantirPropriedade($alocacao, $idUsuario);

        $idAlocacao = (int) $alocacao->id_alocacao;

        if ($this->alocacaoRepository->possuiItensPlanoVinculados($idAlocacao)) {
            throw ValidationException::withMessages([
                'alocacao' => 'Esta alocação está em uso em um plano financeiro. Remova-a dos planos antes de excluir.',
            ]);
        }

        if ($this->movimentacaoSaldoGuardadoRepository->contemMovimentacoesDaAlocacao($idAlocacao)) {
            throw ValidationException::withMessages([
                'alocacao' => 'Esta alocação possui histórico de movimentações e não pode ser excluída.',
            ]);
        }

        $this->alocacaoRepository->excluir($alocacao);
    }

    public function transferirEntreAlocacoes(Alocacao $alocacaoOrigem, int $idUsuario, array $dados): MovimentacaoSaldoGuardado
    {
        $this->garantirPropriedade($alocacaoOrigem, $idUsuario);

        $idAlocacaoOrigem = (int) $alocacaoOrigem->id_alocacao;
        $idAlocacaoDestino = (int) $dados['id_alocacao_destino'];
        $alocacaoDestino = $this->alocacaoRepository->buscarAlocacaoDoUsuario($idAlocacaoDestino, $idUsuario);

        if (! $alocacaoDestino) {
            throw ValidationException::withMessages([
                'id_alocacao_destino' => 'Selecione a alocação que vai receber o valor.',
            ]);
        }

        if ($idAlocacaoDestino === $idAlocacaoOrigem) {
            throw ValidationException::withMessages([
                'id_alocacao_destino' => 'A alocação de destino deve ser diferente da origem.',
            ]);
        }

        $contaBancaria = $this->buscarContaDoUsuario((int) $dados['id_conta_bancaria'], $idUsuario, 'id_conta_bancaria');
        $valor = $this->normalizarValorPositivo((float) $dados['valor'], 'O valor da transferência deve ser maior que zero.');

        $this->alocacaoRepository->bloquearAlocacoesParaMovimentacao([$idAlocacaoOrigem, $idAlocacaoDestino]);
        $this->garantirSaldoSuficienteNaConta(
            $idAlocacaoOrigem,
            (int) $contaBancaria->id_conta_bancaria,
            $valor,
            'O valor não pode ser maior que o saldo da alocação nesta conta.',
        );

        $movimentacao = $this->movimentacaoSaldoGuardadoRepository->criar([
            'id_usuario' => $idUsuario,
            'id_conta_bancaria' => $contaBancaria->id_conta_bancaria,
            'id_conta_bancaria_origem' => null,
            'id_conta_bancaria_resgate' => null,
            'id_item_plano' => null,
            'id_alocacao' => $idAlocacaoDestino,
            'id_alocacao_origem' => $idAlocacaoOrigem,
            'id_lancamento_origem' => null,
            'id_aporte_objetivo' => null,
            'tipo' => TipoMovimentacaoSaldoGuardado::Transferencia,
            'valor' => $valor,
            'data_movimentacao' => $dados['data_transferencia'],
            'observacao' => $dados['observacao'] ?? null,
        ]);

        $this->consolidadorSaldoAlocacao->recalcularSaldosConsolidadosDasAlocacoes([$idAlocacaoOrigem, $idAlocacaoDestino]);
        DashboardCache::invalidar($idUsuario);

        return $movimentacao;
    }

    public function resgatarAlocacaoParaConta(Alocacao $alocacao, int $idUsuario, array $dados): MovimentacaoSaldoGuardado
    {
        $this->garantirPropriedade($alocacao, $idUsuario);

        $idAlocacao = (int) $alocacao->id_alocacao;
        $contaSaida = $this->buscarContaDoUsuario((int) $dados['id_conta_bancaria_saida'], $idUsuario, 'id_conta_bancaria_saida');
        $contaDestino = $this->buscarContaDoUsuario((int) $dados['id_conta_bancaria_destino'], $idUsuario, 'id_conta_bancaria_destino');

        if ($contaDestino->arquivada === SimNao::Sim) {
            throw ValidationException::withMessages([
                'id_conta_bancaria_destino' => 'Não é possível resgatar para uma conta arquivada.',
            ]);
        }

        $valor = $this->normalizarValorPositivo((float) $dados['valor'], 'O valor do resgate deve ser maior que zero.');

        $this->alocacaoRepository->bloquearAlocacoesParaMovimentacao([$idAlocacao]);
        $this->garantirSaldoSuficienteNaConta(
            $idAlocacao,
            (int) $contaSaida->id_conta_bancaria,
            $valor,
            'O valor do resgate não pode ser maior que o saldo da alocação nesta conta.',
        );

        $resgateParaOutraConta = (int) $contaDestino->id_conta_bancaria !== (int) $contaSaida->id_conta_bancaria;

        $movimentacao = $this->movimentacaoSaldoGuardadoRepository->criar([
            'id_usuario' => $idUsuario,
            'id_conta_bancaria' => $contaSaida->id_conta_bancaria,
            'id_conta_bancaria_origem' => null,
            'id_conta_bancaria_resgate' => $resgateParaOutraConta ? $contaDestino->id_conta_bancaria : null,
            'id_item_plano' => null,
            'id_alocacao' => $idAlocacao,
            'id_alocacao_origem' => null,
            'id_lancamento_origem' => null,
            'id_aporte_objetivo' => null,
            'tipo' => TipoMovimentacaoSaldoGuardado::Resgate,
            'valor' => $valor,
            'data_movimentacao' => $dados['data_resgate'],
            'observacao' => $dados['observacao'] ?? null,
        ]);

        $this->consolidadorSaldoAlocacao->recalcularSaldoConsolidadoDaAlocacao($idAlocacao);
        DashboardCache::invalidar($idUsuario);

        return $movimentacao;
    }

    public function garantirPropriedade(Alocacao $alocacao, int $idUsuario): void
    {
        if ((int) $alocacao->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Esta alocação não pertence ao usuário autenticado.');
        }
    }

    private function montarSaldosPorConta(array $saldosDaAlocacao, Collection $contasPorId): array
    {
        return collect($saldosDaAlocacao)
            ->filter(fn (float $saldo) => abs($saldo) >= 0.01)
            ->map(fn (float $saldo, int $idContaBancaria) => [
                'id_conta_bancaria' => $idContaBancaria,
                'nome' => $contasPorId->get($idContaBancaria)?->nome ?? 'Conta removida',
                'saldo' => round($saldo, 2),
            ])
            ->sortByDesc('saldo')
            ->values()
            ->all();
    }

    private function garantirSaldoSuficienteNaConta(int $idAlocacao, int $idContaBancaria, float $valor, string $mensagem): void
    {
        $saldoNaConta = $this->consolidadorSaldoAlocacao->calcularSaldoDaAlocacaoNaConta($idAlocacao, $idContaBancaria);

        if ($valor > $saldoNaConta) {
            throw ValidationException::withMessages([
                'valor' => $mensagem,
            ]);
        }
    }

    private function buscarContaDoUsuario(int $idContaBancaria, int $idUsuario, string $campoErro): ContaBancaria
    {
        $contaBancaria = $idContaBancaria > 0
            ? $this->contaBancariaRepository->buscarParaUsuario($idContaBancaria, $idUsuario)
            : null;

        if (! $contaBancaria) {
            throw ValidationException::withMessages([
                $campoErro => 'Selecione uma conta bancária válida.',
            ]);
        }

        return $contaBancaria;
    }

    private function normalizarValorPositivo(float $valor, string $mensagem): float
    {
        $valorArredondado = round($valor, 2);

        if ($valorArredondado <= 0) {
            throw ValidationException::withMessages([
                'valor' => $mensagem,
            ]);
        }

        return $valorArredondado;
    }

    private function normalizarNomeAlocacao(string $nome): string
    {
        $nomeNormalizado = trim(preg_replace('/\s+/', ' ', $nome) ?? '');

        if ($nomeNormalizado === '') {
            throw ValidationException::withMessages([
                'nome' => 'Informe o nome da alocação.',
            ]);
        }

        return mb_substr($nomeNormalizado, 0, 120);
    }

    private function garantirNomeDisponivel(int $idUsuario, string $nome, ?int $idAlocacaoIgnorada = null): void
    {
        if ($this->alocacaoRepository->existeAlocacaoComNome($idUsuario, $nome, $idAlocacaoIgnorada)) {
            throw ValidationException::withMessages([
                'nome' => 'Já existe uma alocação com este nome.',
            ]);
        }
    }
}
