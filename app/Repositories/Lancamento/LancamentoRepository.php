<?php

namespace App\Repositories\Lancamento;

use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Models\Lancamento\Lancamento;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LancamentoRepository
{
    public function listarDoMes(
        int $idUsuario,
        int $ano,
        int $mes,
        array $filtros = [],
        int $porPagina = 20,
    ): LengthAwarePaginator {
        $query = Lancamento::query()
            ->with(['categoria.pai', 'contaBancaria', 'cartaoCredito', 'pai'])
            ->ocorrenciasDoMes($idUsuario, $ano, $mes)
            ->when(
                ($filtros['tipo'] ?? null),
                fn ($q, $tipo) => $q->where('tipo', $tipo)
            )
            ->when(
                ($filtros['situacao'] ?? null),
                fn ($q, $situacao) => $q->where('situacao', $situacao),
                fn ($q) => $q->where('situacao', '!=', SituacaoLancamento::Cancelado)
            )
            ->when(
                ($filtros['id_categoria'] ?? null),
                fn ($q, $idCategoria) => $q->where('id_categoria', $idCategoria)
            )
            ->orderBy('data_vencimento')
            ->orderBy('id_lancamento');

        return $query->paginate($porPagina)->withQueryString();
    }

    public function totaisDoMes(int $idUsuario, int $ano, int $mes): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        $totais = Lancamento::query()
            ->where('id_usuario', $idUsuario)
            ->where('eh_recorrencia', SimNao::Nao)
            ->where('situacao', '!=', SituacaoLancamento::Cancelado)
            ->whereBetween('data_vencimento', [$inicio, $fim])
            ->selectRaw('tipo, situacao, SUM(valor) as total')
            ->groupBy('tipo', 'situacao')
            ->get();

        $resultado = [
            'receitas' => 0.0,
            'despesas' => 0.0,
            'receitas_pagas' => 0.0,
            'despesas_pagas' => 0.0,
            'pendentes' => 0.0,
        ];

        foreach ($totais as $linha) {
            $valor = (float) $linha->total;
            $tipo = $linha->tipo instanceof TipoLancamento
                ? $linha->tipo
                : TipoLancamento::from($linha->tipo);
            $situacao = $linha->situacao instanceof SituacaoLancamento
                ? $linha->situacao
                : SituacaoLancamento::from($linha->situacao);

            if ($tipo === TipoLancamento::Receita) {
                $resultado['receitas'] += $valor;
                if ($situacao === SituacaoLancamento::Pago) {
                    $resultado['receitas_pagas'] += $valor;
                }
            } else {
                $resultado['despesas'] += $valor;
                if ($situacao === SituacaoLancamento::Pago) {
                    $resultado['despesas_pagas'] += $valor;
                }
            }

            if ($situacao === SituacaoLancamento::Pendente) {
                $resultado['pendentes'] += $valor;
            }
        }

        $resultado['saldo'] = $resultado['receitas'] - $resultado['despesas'];

        return $resultado;
    }

    public function totaisPendentesAnteriores(int $idUsuario, int $ano, int $mes): array
    {
        $inicioMes = sprintf('%04d-%02d-01', $ano, $mes);

        $linhas = Lancamento::query()
            ->where('id_usuario', $idUsuario)
            ->where('eh_recorrencia', SimNao::Nao)
            ->where('situacao', SituacaoLancamento::Pendente)
            ->where('data_vencimento', '<', $inicioMes)
            ->selectRaw('tipo, SUM(valor) as total')
            ->groupBy('tipo')
            ->get();

        $receitas = 0.0;
        $despesas = 0.0;

        foreach ($linhas as $linha) {
            $valor = (float) $linha->total;
            $tipo = $linha->tipo instanceof TipoLancamento
                ? $linha->tipo
                : TipoLancamento::from($linha->tipo);

            if ($tipo === TipoLancamento::Receita) {
                $receitas += $valor;
            } else {
                $despesas += $valor;
            }
        }

        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
        ];
    }

    public function criar(array $dados): Lancamento
    {
        return Lancamento::query()->create($dados);
    }

    public function criarVarios(array $linhas): Collection
    {
        $criados = collect();

        foreach ($linhas as $linha) {
            $criados->push($this->criar($linha));
        }

        return $criados;
    }

    public function atualizar(Lancamento $lancamento, array $dados): Lancamento
    {
        $lancamento->update($dados);

        return $lancamento->refresh();
    }

    public function excluir(Lancamento $lancamento): void
    {
        $lancamento->delete();
    }

    public function buscarPaisRecorrentesAtivos(int $idUsuario): Collection
    {
        return Lancamento::query()
            ->where('id_usuario', $idUsuario)
            ->where('eh_recorrencia', SimNao::Sim)
            ->where('situacao', '!=', SituacaoLancamento::Cancelado)
            ->get();
    }

    public function existeOcorrenciaNaData(int $idPai, Carbon $data): bool
    {
        return Lancamento::query()
            ->where('id_lancamento_pai', $idPai)
            ->whereDate('data_vencimento', $data->toDateString())
            ->exists();
    }

    public function temLancamentosNaConta(int $idContaBancaria): bool
    {
        return Lancamento::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->exists();
    }

    public function temLancamentosNaCategoria(int $idCategoria): bool
    {
        return Lancamento::query()
            ->where('id_categoria', $idCategoria)
            ->exists();
    }

    public function contarPorConta(int $idContaBancaria): int
    {
        return Lancamento::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->count();
    }

    public function saldoMovimentadoDaConta(int $idContaBancaria): float
    {
        $receitas = (float) Lancamento::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->where('tipo', TipoLancamento::Receita)
            ->where('situacao', SituacaoLancamento::Pago)
            ->where('eh_recorrencia', SimNao::Nao)
            ->sum('valor');

        $despesas = (float) Lancamento::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->where('tipo', TipoLancamento::Despesa)
            ->where('situacao', SituacaoLancamento::Pago)
            ->where('eh_recorrencia', SimNao::Nao)
            ->sum('valor');

        return $receitas - $despesas;
    }

    public function cancelarFuturasDoPai(int $idPai, Carbon $aPartirDe): int
    {
        return Lancamento::query()
            ->where('id_lancamento_pai', $idPai)
            ->whereDate('data_vencimento', '>=', $aPartirDe->toDateString())
            ->where('situacao', SituacaoLancamento::Pendente)
            ->update(['situacao' => SituacaoLancamento::Cancelado]);
    }

    public function somarDespesasDasCategoriasNoMes(
        int $idUsuario,
        array $idsCategorias,
        int $ano,
        int $mes,
        ?int $excetoIdLancamento = null,
    ): float {
        if ($idsCategorias === []) {
            return 0.0;
        }

        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        return (float) Lancamento::query()
            ->where('id_usuario', $idUsuario)
            ->where('tipo', TipoLancamento::Despesa)
            ->where('eh_recorrencia', SimNao::Nao)
            ->where('situacao', '!=', SituacaoLancamento::Cancelado)
            ->whereIn('id_categoria', $idsCategorias)
            ->whereBetween('data_vencimento', [$inicio, $fim])
            ->when(
                $excetoIdLancamento,
                fn ($query, int $id) => $query->where('id_lancamento', '!=', $id)
            )
            ->sum('valor');
    }

    public function somarPendenciasEmContasNoPeriodo(
        int $idUsuario,
        Carbon $inicio,
        Carbon $fim,
        bool $incluirAtrasadas = false,
    ): array {
        $linhas = Lancamento::query()
            ->where('id_usuario', $idUsuario)
            ->where('eh_recorrencia', SimNao::Nao)
            ->where('situacao', SituacaoLancamento::Pendente)
            ->whereNotNull('id_conta_bancaria')
            ->when(
                $incluirAtrasadas,
                fn ($query) => $query->where('data_vencimento', '<=', $fim->toDateString()),
                fn ($query) => $query->whereBetween('data_vencimento', [
                    $inicio->toDateString(),
                    $fim->toDateString(),
                ])
            )
            ->selectRaw('tipo, SUM(valor) as total')
            ->groupBy('tipo')
            ->get();

        return $this->agregarReceitasDespesas($linhas);
    }

    public function somarPendenciasEmContasNoMes(int $idUsuario, int $ano, int $mes): array
    {
        $inicio = Carbon::create($ano, $mes, 1)->startOfDay();
        $fim = $inicio->copy()->endOfMonth()->startOfDay();

        return $this->somarPendenciasEmContasNoPeriodo($idUsuario, $inicio, $fim);
    }

    public function somarPendenciasAtrasadasEmContas(int $idUsuario, Carbon $antesDe): array
    {
        $linhas = Lancamento::query()
            ->where('id_usuario', $idUsuario)
            ->where('eh_recorrencia', SimNao::Nao)
            ->where('situacao', SituacaoLancamento::Pendente)
            ->whereNotNull('id_conta_bancaria')
            ->where('data_vencimento', '<', $antesDe->toDateString())
            ->selectRaw('tipo, SUM(valor) as total')
            ->groupBy('tipo')
            ->get();

        return $this->agregarReceitasDespesas($linhas);
    }

    private function agregarReceitasDespesas(Collection $linhas): array
    {
        $receitas = 0.0;
        $despesas = 0.0;

        foreach ($linhas as $linha) {
            $valor = (float) $linha->total;
            $tipo = $linha->tipo instanceof TipoLancamento
                ? $linha->tipo
                : TipoLancamento::from($linha->tipo);

            if ($tipo === TipoLancamento::Receita) {
                $receitas += $valor;
            } else {
                $despesas += $valor;
            }
        }

        return [
            'receitas' => round($receitas, 2),
            'despesas' => round($despesas, 2),
        ];
    }
}
