<?php

namespace App\Services\Financeiro;

use App\Enum\OrigemLancamentoFinanceiro;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Http\Resources\EspacoCompartilhado\ItemFinanceiroUnificadoResource;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\Lancamento\Lancamento;
use App\Models\Usuario\Usuario;
use App\Repositories\EspacoCompartilhado\EspacoCompartilhadoRepository;
use App\Repositories\EspacoCompartilhado\LancamentoCompartilhadoRepository;
use App\Repositories\Lancamento\LancamentoRepository;
use App\Services\EspacoCompartilhado\RecorrenciaCompartilhadaService;
use App\Services\Lancamento\RecorrenciaService;
use App\Services\Renda\RendaGeracaoService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class ConsultaFinanceiraUsuarioService
{
    /** @var array<int, bool> */
    private array $cacheInclusaoCompartilhados = [];

    public function __construct(
        private readonly LancamentoRepository $lancamentoRepository,
        private readonly LancamentoCompartilhadoRepository $lancamentoCompartilhadoRepository,
        private readonly RecorrenciaService $recorrenciaService,
        private readonly RendaGeracaoService $rendaGeracaoService,
        private readonly RecorrenciaCompartilhadaService $recorrenciaCompartilhadaService,
        private readonly EspacoCompartilhadoRepository $espacoCompartilhadoRepository,
    ) {}

    public function listarDoMes(int $idUsuario, int $ano, int $mes, array $filtros = [], int $porPagina = 20): LengthAwarePaginator
    {
        $this->gerarDependenciasDoMes($idUsuario, $ano, $mes);

        $pessoais = $this->lancamentoRepository->listarDoMesSemPaginacao($idUsuario, $ano, $mes, $filtros);
        $compartilhados = $this->deveIncluirLancamentosCompartilhados($idUsuario)
            ? $this->lancamentoCompartilhadoRepository->listarDoResponsavelNoMes($idUsuario, $ano, $mes, $filtros)
            : collect();

        $itens = $pessoais
            ->map(fn (Lancamento $lancamento) => $this->montarItemPessoal($lancamento))
            ->concat($compartilhados->map(fn (LancamentoCompartilhado $lancamento) => $this->montarItemCompartilhado($lancamento)))
            ->sort(function (array $primeiro, array $segundo) {
                $comparacaoData = strcmp((string) $primeiro['data_vencimento'], (string) $segundo['data_vencimento']);

                if ($comparacaoData !== 0) {
                    return $comparacaoData;
                }

                return strcmp((string) $primeiro['chave_ordenacao'], (string) $segundo['chave_ordenacao']);
            })
            ->values();

        $paginaAtual = Paginator::resolveCurrentPage();
        $fatia = $itens->forPage($paginaAtual, $porPagina)->values();

        return new Paginator(
            $fatia,
            $itens->count(),
            $porPagina,
            $paginaAtual,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }

    public function totaisDoMes(int $idUsuario, int $ano, int $mes): array
    {
        $this->gerarDependenciasDoMes($idUsuario, $ano, $mes);

        $pessoais = $this->lancamentoRepository->totaisDoMes($idUsuario, $ano, $mes);

        if (! $this->deveIncluirLancamentosCompartilhados($idUsuario)) {
            return $pessoais;
        }

        $compartilhados = $this->lancamentoCompartilhadoRepository->somarTotaisDoResponsavelNoMes($idUsuario, $ano, $mes);

        $pessoais['despesas'] = round($pessoais['despesas'] + $compartilhados['despesas'], 2);
        $pessoais['despesas_pagas'] = round($pessoais['despesas_pagas'] + $compartilhados['despesas_pagas'], 2);
        $pessoais['pendentes'] = round($pessoais['pendentes'] + $compartilhados['pendentes'], 2);
        $pessoais['quantidade_despesas'] = $pessoais['quantidade_despesas'] + $compartilhados['quantidade_despesas'];

        return $pessoais;
    }

    public function contarPorTipoDoMes(int $idUsuario, int $ano, int $mes, array $filtros = []): array
    {
        $this->gerarDependenciasDoMes($idUsuario, $ano, $mes);

        $contadores = $this->lancamentoRepository->contarPorTipoDoMes($idUsuario, $ano, $mes, $filtros);

        if (! $this->deveIncluirLancamentosCompartilhados($idUsuario)) {
            return $contadores;
        }

        $quantidadeCompartilhada = $this->lancamentoCompartilhadoRepository->contarDoResponsavelNoMes($idUsuario, $ano, $mes, $filtros);

        $contadores['despesas'] += $quantidadeCompartilhada;
        $contadores['todos'] += $quantidadeCompartilhada;

        return $contadores;
    }

    public function somarDespesasDasCategoriasNoMes(
        int $idUsuario,
        array $idsCategorias,
        int $ano,
        int $mes,
        ?int $excetoIdLancamentoPessoal = null,
        ?int $excetoIdLancamentoCompartilhado = null,
    ): float {
        $pessoal = $this->lancamentoRepository->somarDespesasDasCategoriasNoMes(
            $idUsuario,
            $idsCategorias,
            $ano,
            $mes,
            $excetoIdLancamentoPessoal
        );

        if (! $this->deveIncluirLancamentosCompartilhados($idUsuario)) {
            return round($pessoal, 2);
        }

        $compartilhado = $this->lancamentoCompartilhadoRepository->somarDespesasDasCategoriasDoResponsavelNoMes(
            $idUsuario,
            $idsCategorias,
            $ano,
            $mes,
            $excetoIdLancamentoCompartilhado
        );

        return round($pessoal + $compartilhado, 2);
    }

    public function despesasAgrupadasPorCategoriaNoMes(int $idUsuario, int $ano, int $mes): Collection
    {
        $pessoais = $this->lancamentoRepository->despesasAgrupadasPorCategoriaNoMes($idUsuario, $ano, $mes);
        $compartilhados = $this->deveIncluirLancamentosCompartilhados($idUsuario)
            ? $this->lancamentoCompartilhadoRepository->despesasAgrupadasPorCategoriaDoResponsavelNoMes($idUsuario, $ano, $mes)
            : collect();

        return $pessoais
            ->concat($compartilhados)
            ->groupBy('id_categoria')
            ->map(function (Collection $grupo) {
                $base = $grupo->first();

                return (object) [
                    'id_categoria' => (int) $base->id_categoria,
                    'nome' => (string) $base->nome,
                    'cor' => $base->cor,
                    'icone' => $base->icone,
                    'valor' => round((float) $grupo->sum('valor'), 2),
                ];
            })
            ->sortByDesc('valor')
            ->values();
    }

    public function somarDespesasPagasDaContaSeModuloHabilitado(int $idUsuario, int $idContaBancaria): float
    {
        if (! $this->deveIncluirLancamentosCompartilhados($idUsuario)) {
            return 0.0;
        }

        return $this->lancamentoCompartilhadoRepository->somarDespesasPagasDaConta($idContaBancaria);
    }

    public function somarDespesasPagasDoCartaoSeModuloHabilitado(int $idUsuario, int $idCartaoCredito): float
    {
        if (! $this->deveIncluirLancamentosCompartilhados($idUsuario)) {
            return 0.0;
        }

        return $this->lancamentoCompartilhadoRepository->somarDespesasPagasDoCartao($idCartaoCredito);
    }

    public function listarPendentesCompartilhadosDoResponsavelAte(int $idUsuario, CarbonInterface $ate): Collection
    {
        if (! $this->deveIncluirLancamentosCompartilhados($idUsuario)) {
            return collect();
        }

        return $this->lancamentoCompartilhadoRepository
            ->listarPendentesDoResponsavelAte($idUsuario, $ate)
            ->map(fn (LancamentoCompartilhado $lancamento) => $this->montarItemCompartilhado($lancamento));
    }

    public function listarPendentesUnificadosAte(
        int $idUsuario,
        CarbonInterface $hoje,
        CarbonInterface $ate,
        int $limite,
        string $iconePadrao,
        string $corPadrao,
    ): Collection {
        $pessoais = $this->lancamentoRepository
            ->listarDespesasPendentesAte($idUsuario, Carbon::parse($ate->toDateString()))
            ->map(fn (Lancamento $lancamento) => [
                'id' => (int) $lancamento->id_lancamento,
                'descricao' => $lancamento->descricao,
                'valor_numero' => (float) $lancamento->valor,
                'data_vencimento' => $lancamento->data_vencimento?->format('Y-m-d'),
                'data_vencimento_formatada' => $lancamento->data_vencimento?->format('d/m/Y'),
                'conta_bancaria_nome' => $lancamento->contaBancaria?->nome
                    ?? $lancamento->cartaoCredito?->nome,
                'icone' => $lancamento->categoria?->icone ?: $iconePadrao,
                'cor' => $lancamento->categoria?->cor ?: $corPadrao,
                'atrasado' => (bool) $lancamento->data_vencimento?->lt($hoje),
                'origem' => OrigemLancamentoFinanceiro::Pessoal->value,
                'url_situacao' => route('lancamentos.situacao', $lancamento),
            ]);

        $compartilhados = $this->listarPendentesCompartilhadosDoResponsavelAte($idUsuario, $ate)
            ->map(fn (array $item) => [
                'id' => (int) $item['id_referencia'],
                'descricao' => $item['descricao'],
                'valor_numero' => (float) $item['valor_numero'],
                'data_vencimento' => $item['data_vencimento'],
                'data_vencimento_formatada' => $item['data_vencimento_formatada'],
                'conta_bancaria_nome' => $item['nome_espaco'] ? "Espaço {$item['nome_espaco']}" : 'Compartilhado',
                'icone' => $item['categoria_icone'] ?: $iconePadrao,
                'cor' => $item['categoria_cor'] ?: $corPadrao,
                'atrasado' => Carbon::parse($item['data_vencimento'])->lt($hoje),
                'origem' => OrigemLancamentoFinanceiro::Compartilhado->value,
                'url_situacao' => $item['url_espaco'],
            ]);

        return $pessoais
            ->concat($compartilhados)
            ->sortBy([
                ['data_vencimento', 'asc'],
                ['descricao', 'asc'],
            ])
            ->take($limite)
            ->values();
    }

    public function resumirPendentesUnificadosAte(int $idUsuario, CarbonInterface $ate): array
    {
        $pessoal = $this->lancamentoRepository->resumirDespesasPendentesAte(
            $idUsuario,
            Carbon::parse($ate->toDateString())
        );

        if (! $this->deveIncluirLancamentosCompartilhados($idUsuario)) {
            return $pessoal;
        }

        $compartilhados = $this->lancamentoCompartilhadoRepository->listarPendentesDoResponsavelAte($idUsuario, $ate);
        $totalCompartilhado = round((float) $compartilhados->sum('valor'), 2);
        $quantidadeCompartilhada = $compartilhados->count();

        return [
            'total' => round($pessoal['total'] + $totalCompartilhado, 2),
            'quantidade' => $pessoal['quantidade'] + $quantidadeCompartilhada,
        ];
    }

    public function somarDespesasRecorrentesDoMes(int $idUsuario, int $ano, int $mes): float
    {
        $pessoal = $this->lancamentoRepository->somarDespesasRecorrentesDoMes($idUsuario, $ano, $mes);

        if (! $this->deveIncluirLancamentosCompartilhados($idUsuario)) {
            return $pessoal;
        }

        $compartilhado = $this->lancamentoCompartilhadoRepository->somarDespesasRecorrentesDoResponsavelNoMes($idUsuario, $ano, $mes);

        return round($pessoal + $compartilhado, 2);
    }

    public function resolverItensDoPaginator(LengthAwarePaginator $paginator): array
    {
        return ItemFinanceiroUnificadoResource::collection(collect($paginator->items()))->resolve();
    }

    private function deveIncluirLancamentosCompartilhados(int $idUsuario): bool
    {
        if (array_key_exists($idUsuario, $this->cacheInclusaoCompartilhados)) {
            return $this->cacheInclusaoCompartilhados[$idUsuario];
        }

        $usuario = Usuario::query()->find($idUsuario);
        $deveIncluir = $usuario?->possuiCompartilhamentoHabilitado() ?? false;

        return $this->cacheInclusaoCompartilhados[$idUsuario] = $deveIncluir;
    }

    private function gerarDependenciasDoMes(int $idUsuario, int $ano, int $mes): void
    {
        $this->recorrenciaService->gerarParaMes($idUsuario, $ano, $mes);
        $this->rendaGeracaoService->gerarParaMes($idUsuario, $ano, $mes);

        if (! $this->deveIncluirLancamentosCompartilhados($idUsuario)) {
            return;
        }

        foreach ($this->espacoCompartilhadoRepository->listarEspacosAtivosDoUsuario($idUsuario) as $espaco) {
            $this->recorrenciaCompartilhadaService->gerarOcorrenciasAteOFimDoMes(
                (int) $espaco->id_espaco_compartilhado,
                $ano,
                $mes
            );
        }
    }

    private function montarItemPessoal(Lancamento $lancamento): array
    {
        $payload = (new \App\Http\Resources\Lancamento\LancamentoResource($lancamento))->resolve();

        return [
            ...$payload,
            'origem' => OrigemLancamentoFinanceiro::Pessoal->value,
            'origem_rotulo' => OrigemLancamentoFinanceiro::Pessoal->rotulo(),
            'nome_espaco' => null,
            'id_referencia' => (int) $lancamento->id_lancamento,
            'chave_ordenacao' => 'pessoal-'.$lancamento->id_lancamento,
            'tipo' => $payload['tipo'] ?? TipoLancamento::Despesa->value,
            'pode_editar_na_lista_pessoal' => true,
        ];
    }

    private function montarItemCompartilhado(LancamentoCompartilhado $lancamento): array
    {
        $categoria = $lancamento->relationLoaded('categoria') ? $lancamento->categoria : null;
        $categoriaPai = $categoria?->relationLoaded('pai') ? $categoria->pai : null;
        $espaco = $lancamento->relationLoaded('espaco') ? $lancamento->espaco : null;
        $estaPago = $lancamento->estaPago();

        return [
            'id' => (int) $lancamento->id_lancamento_compartilhado,
            'chave' => $lancamento->getRouteKey(),
            'descricao' => $lancamento->descricao,
            'valor' => number_format((float) $lancamento->valor, 2, ',', '.'),
            'valor_numero' => (float) $lancamento->valor,
            'data_vencimento' => $lancamento->data_vencimento?->format('Y-m-d'),
            'data_vencimento_formatada' => $lancamento->data_vencimento?->format('d/m/Y'),
            'data_pagamento' => $lancamento->data_pagamento?->format('Y-m-d'),
            'tipo' => TipoLancamento::Despesa->value,
            'tipo_rotulo' => TipoLancamento::Despesa->rotulo(),
            'forma_pagamento' => $lancamento->forma_pagamento?->value,
            'forma_pagamento_rotulo' => $lancamento->forma_pagamento?->rotulo(),
            'id_conta_bancaria' => $lancamento->id_conta_bancaria,
            'conta_bancaria_nome' => $lancamento->relationLoaded('contaBancaria') ? $lancamento->contaBancaria?->nome : null,
            'id_cartao_credito' => $lancamento->id_cartao_credito,
            'cartao_credito_nome' => $lancamento->relationLoaded('cartaoCredito') ? $lancamento->cartaoCredito?->nome : null,
            'situacao' => $estaPago ? SituacaoLancamento::Pago->value : SituacaoLancamento::Pendente->value,
            'situacao_rotulo' => $estaPago ? SituacaoLancamento::Pago->rotulo() : SituacaoLancamento::Pendente->rotulo(),
            'esta_efetivado' => $estaPago,
            'id_categoria' => $lancamento->id_categoria,
            'id_categoria_principal' => $categoriaPai
                ? (int) $categoriaPai->id_categoria
                : ($categoria ? (int) $categoria->id_categoria : null),
            'id_subcategoria' => $categoriaPai ? (int) $categoria->id_categoria : null,
            'categoria_nome' => $categoria?->nome,
            'categoria_cor' => $categoria?->cor ?? $categoriaPai?->cor,
            'categoria_icone' => $categoria?->icone ?? $categoriaPai?->icone,
            'observacao' => $lancamento->observacao,
            'eh_recorrente' => $lancamento->pertenceARecorrencia(),
            'eh_parcelado' => false,
            'origem' => OrigemLancamentoFinanceiro::Compartilhado->value,
            'origem_rotulo' => OrigemLancamentoFinanceiro::Compartilhado->rotulo(),
            'nome_espaco' => $espaco?->nome,
            'id_referencia' => (int) $lancamento->id_lancamento_compartilhado,
            'chave_ordenacao' => 'compartilhado-'.$lancamento->id_lancamento_compartilhado,
            'pode_editar_na_lista_pessoal' => false,
            'url_espaco' => $espaco
                ? route('compartilhados.show', [
                    'espacoCompartilhado' => $espaco,
                    'ano' => (int) $lancamento->data_vencimento?->year,
                    'mes' => (int) $lancamento->data_vencimento?->month,
                ])
                : null,
            'url_atualizar' => null,
            'url_excluir' => null,
            'url_situacao' => null,
        ];
    }
}
