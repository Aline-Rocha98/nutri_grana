<?php

namespace App\Repositories\EspacoCompartilhado;

use App\Enum\StatusLancamentoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LancamentoCompartilhadoRepository
{
    public function listarLancamentosDoMes(int $idEspaco, int $ano, int $mes, ?string $status = null): Collection
    {
        return $this->consultarLancamentosDoMes($idEspaco, $ano, $mes)
            ->with([
                'responsavel:id_usuario,nome,foto_perfil',
                'categoria:id_categoria,id_categoria_pai,nome,icone,cor,tipo',
                'categoria.pai:id_categoria,nome,icone,cor',
            ])
            ->when(
                $status,
                fn (Builder $consulta, string $statusFiltrado) => $consulta->where('status', $statusFiltrado),
                fn (Builder $consulta) => $consulta->where('status', '!=', StatusLancamentoCompartilhado::Cancelado)
            )
            ->orderBy('data_vencimento')
            ->orderBy('id_lancamento_compartilhado')
            ->get();
    }

    public function somarTotaisDoMesPorResponsavelEStatus(int $idEspaco, int $ano, int $mes, CarbonInterface $hoje): Collection
    {
        return $this->consultarLancamentosDoMes($idEspaco, $ano, $mes)
            ->where('status', '!=', StatusLancamentoCompartilhado::Cancelado)
            ->selectRaw(
                'id_usuario_responsavel, status, COUNT(*) as quantidade, SUM(valor) as total, '
                .'SUM(CASE WHEN data_vencimento < ? THEN valor ELSE 0 END) as total_vencido, '
                .'SUM(CASE WHEN data_vencimento < ? THEN 1 ELSE 0 END) as quantidade_vencida',
                [$hoje->toDateString(), $hoje->toDateString()]
            )
            ->groupBy('id_usuario_responsavel', 'status')
            ->toBase()
            ->get();
    }

    public function criarLancamentoCompartilhado(array $dados): LancamentoCompartilhado
    {
        return LancamentoCompartilhado::query()->create($dados);
    }

    public function atualizarLancamentoCompartilhado(LancamentoCompartilhado $lancamento, array $dados): LancamentoCompartilhado
    {
        $lancamento->update($dados);

        return $lancamento->refresh();
    }

    public function excluirLancamentoCompartilhado(LancamentoCompartilhado $lancamento): void
    {
        $lancamento->delete();
    }

    public function bloquearLancamentoParaAtualizacao(int $idLancamento): ?LancamentoCompartilhado
    {
        return LancamentoCompartilhado::query()
            ->whereKey($idLancamento)
            ->lockForUpdate()
            ->first();
    }

    public function existeOcorrenciaDaRecorrenciaNaData(int $idRecorrencia, CarbonInterface $data): bool
    {
        return LancamentoCompartilhado::query()
            ->where('id_recorrencia_compartilhada', $idRecorrencia)
            ->whereDate('data_vencimento', $data->toDateString())
            ->exists();
    }

    public function buscarPrimeiraOcorrenciaDaRecorrencia(int $idRecorrencia): LancamentoCompartilhado
    {
        return LancamentoCompartilhado::query()
            ->where('id_recorrencia_compartilhada', $idRecorrencia)
            ->orderBy('data_vencimento')
            ->firstOrFail();
    }

    public function cancelarOcorrenciasPendentesDaRecorrenciaAPartirDe(int $idRecorrencia, CarbonInterface $aPartirDe): int
    {
        return LancamentoCompartilhado::query()
            ->where('id_recorrencia_compartilhada', $idRecorrencia)
            ->where('status', StatusLancamentoCompartilhado::Pendente)
            ->whereDate('data_vencimento', '>=', $aPartirDe->toDateString())
            ->update([
                'status' => StatusLancamentoCompartilhado::Cancelado,
                'versao' => DB::raw('versao + 1'),
            ]);
    }

    public function listarDoResponsavelNoMes(int $idUsuarioResponsavel, int $ano, int $mes, array $filtros = []): Collection
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        return $this->consultaDoResponsavel($idUsuarioResponsavel)
            ->with([
                'espaco:id_espaco_compartilhado,nome',
                'categoria:id_categoria,id_categoria_pai,nome,icone,cor,tipo',
                'categoria.pai:id_categoria,nome,icone,cor',
                'contaBancaria:id_conta_bancaria,nome',
                'cartaoCredito:id_cartao_credito,nome',
            ])
            ->whereBetween('data_vencimento', [$inicio, $fim])
            ->tap(fn (Builder $consulta) => $this->aplicarFiltrosDoResponsavel($consulta, $filtros))
            ->orderBy('data_vencimento')
            ->orderBy('id_lancamento_compartilhado')
            ->get();
    }

    public function somarTotaisDoResponsavelNoMes(int $idUsuarioResponsavel, int $ano, int $mes): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        $linhas = $this->consultaDoResponsavel($idUsuarioResponsavel)
            ->whereBetween('data_vencimento', [$inicio, $fim])
            ->selectRaw('status, SUM(valor) as total, COUNT(*) as quantidade')
            ->groupBy('status')
            ->toBase()
            ->get();

        $resultado = [
            'despesas' => 0.0,
            'despesas_pagas' => 0.0,
            'pendentes' => 0.0,
            'quantidade_despesas' => 0,
        ];

        foreach ($linhas as $linha) {
            $valor = (float) $linha->total;
            $quantidade = (int) $linha->quantidade;
            $resultado['despesas'] += $valor;
            $resultado['quantidade_despesas'] += $quantidade;

            if ($linha->status === StatusLancamentoCompartilhado::Pago->value) {
                $resultado['despesas_pagas'] += $valor;
            } else {
                $resultado['pendentes'] += $valor;
            }
        }

        return $resultado;
    }

    public function contarDoResponsavelNoMes(int $idUsuarioResponsavel, int $ano, int $mes, array $filtros = []): int
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        return $this->consultaDoResponsavel($idUsuarioResponsavel)
            ->whereBetween('data_vencimento', [$inicio, $fim])
            ->tap(fn (Builder $consulta) => $this->aplicarFiltrosDoResponsavel($consulta, $filtros))
            ->count();
    }

    public function somarDespesasDasCategoriasDoResponsavelNoMes(
        int $idUsuarioResponsavel,
        array $idsCategorias,
        int $ano,
        int $mes,
        ?int $excetoIdLancamentoCompartilhado = null,
    ): float {
        if ($idsCategorias === []) {
            return 0.0;
        }

        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        return (float) $this->consultaDoResponsavel($idUsuarioResponsavel)
            ->whereIn('id_categoria', $idsCategorias)
            ->whereBetween('data_vencimento', [$inicio, $fim])
            ->when(
                $excetoIdLancamentoCompartilhado,
                fn (Builder $consulta, int $id) => $consulta->where('id_lancamento_compartilhado', '!=', $id)
            )
            ->sum('valor');
    }

    public function despesasAgrupadasPorCategoriaDoResponsavelNoMes(int $idUsuarioResponsavel, int $ano, int $mes): Collection
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        return $this->consultaDoResponsavel($idUsuarioResponsavel)
            ->whereNotNull('lancamentos_compartilhados.id_categoria')
            ->whereBetween('lancamentos_compartilhados.data_vencimento', [$inicio, $fim])
            ->join('categorias', 'categorias.id_categoria', '=', 'lancamentos_compartilhados.id_categoria')
            ->leftJoin('categorias as pais', 'pais.id_categoria', '=', 'categorias.id_categoria_pai')
            ->selectRaw('
                COALESCE(pais.id_categoria, categorias.id_categoria) as id_categoria,
                COALESCE(pais.nome, categorias.nome) as nome,
                COALESCE(pais.cor, categorias.cor) as cor,
                COALESCE(pais.icone, categorias.icone) as icone,
                SUM(lancamentos_compartilhados.valor) as total
            ')
            ->groupByRaw('
                COALESCE(pais.id_categoria, categorias.id_categoria),
                COALESCE(pais.nome, categorias.nome),
                COALESCE(pais.cor, categorias.cor),
                COALESCE(pais.icone, categorias.icone)
            ')
            ->toBase()
            ->get()
            ->map(fn ($linha) => (object) [
                'id_categoria' => (int) $linha->id_categoria,
                'nome' => (string) $linha->nome,
                'cor' => $linha->cor,
                'icone' => $linha->icone,
                'valor' => round((float) $linha->total, 2),
            ]);
    }

    public function somarDespesasPagasDaConta(int $idContaBancaria): float
    {
        return (float) LancamentoCompartilhado::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->where('status', StatusLancamentoCompartilhado::Pago)
            ->sum('valor');
    }

    public function somarDespesasPagasDoCartao(int $idCartaoCredito): float
    {
        return (float) LancamentoCompartilhado::query()
            ->where('id_cartao_credito', $idCartaoCredito)
            ->where('status', StatusLancamentoCompartilhado::Pago)
            ->sum('valor');
    }

    public function listarPendentesDoResponsavelAte(int $idUsuarioResponsavel, CarbonInterface $ate): Collection
    {
        return $this->consultaDoResponsavel($idUsuarioResponsavel)
            ->with([
                'espaco:id_espaco_compartilhado,nome',
                'categoria:id_categoria,id_categoria_pai,nome,icone,cor',
                'categoria.pai:id_categoria,nome,icone,cor',
            ])
            ->where('status', StatusLancamentoCompartilhado::Pendente)
            ->whereDate('data_vencimento', '<=', $ate->toDateString())
            ->orderBy('data_vencimento')
            ->orderBy('id_lancamento_compartilhado')
            ->get();
    }

    private function consultaDoResponsavel(int $idUsuarioResponsavel): Builder
    {
        return LancamentoCompartilhado::query()
            ->where('id_usuario_responsavel', $idUsuarioResponsavel)
            ->where('status', '!=', StatusLancamentoCompartilhado::Cancelado);
    }

    private function aplicarFiltrosDoResponsavel(Builder $consulta, array $filtros): void
    {
        if (($filtros['tipo'] ?? null) === 'receita') {
            $consulta->whereRaw('1 = 0');

            return;
        }

        $consulta
            ->when(
                $filtros['status'] ?? null,
                function (Builder $q, string $status) {
                    if ($status === 'efetivados') {
                        return $q->where('status', StatusLancamentoCompartilhado::Pago);
                    }

                    return $q->where('status', StatusLancamentoCompartilhado::Pendente);
                }
            )
            ->when(
                $filtros['situacao'] ?? null,
                function (Builder $q, string $situacao) {
                    if (in_array($situacao, ['pago', 'recebido'], true)) {
                        return $q->where('status', StatusLancamentoCompartilhado::Pago);
                    }

                    if (in_array($situacao, ['pendente', 'previsto'], true)) {
                        return $q->where('status', StatusLancamentoCompartilhado::Pendente);
                    }

                    return $q->whereRaw('1 = 0');
                }
            )
            ->when(
                $filtros['id_categoria'] ?? null,
                fn (Builder $q, $idCategoria) => $q->where('id_categoria', $idCategoria)
            )
            ->when(
                $filtros['busca'] ?? null,
                function (Builder $q, string $busca) {
                    $termo = '%'.addcslashes($busca, '\\%_').'%';

                    return $q->where(function (Builder $consulta) use ($termo): void {
                        $consulta
                            ->where('descricao', 'like', $termo)
                            ->orWhereHas('categoria', fn (Builder $categoria) => $categoria->where('nome', 'like', $termo))
                            ->orWhereHas('espaco', fn (Builder $espaco) => $espaco->where('nome', 'like', $termo));
                    });
                }
            );
    }

    private function consultarLancamentosDoMes(int $idEspaco, int $ano, int $mes): Builder
    {
        $inicioDoMes = Carbon::create($ano, $mes, 1)->startOfDay();
        $inicioDoProximoMes = $inicioDoMes->copy()->addMonthNoOverflow();

        return LancamentoCompartilhado::query()
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('data_vencimento', '>=', $inicioDoMes->toDateString())
            ->where('data_vencimento', '<', $inicioDoProximoMes->toDateString());
    }
}
