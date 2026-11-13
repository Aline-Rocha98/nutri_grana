<?php

namespace App\Services\Dashboard;

use App\Data\BancosSugeridos;
use App\Enum\PeriodoDashboard;
use App\Enum\TipoOrcamento;
use App\Enum\WidgetDashboard;
use App\Models\Orcamento\Orcamento;
use App\Repositories\CartaoCredito\CartaoCreditoRepository;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Repositories\FaturaCartao\FaturaCartaoRepository;
use App\Repositories\Lancamento\LancamentoRepository;
use App\Repositories\Orcamento\OrcamentoRepository;
use App\Services\Lancamento\RecorrenciaService;
use App\Services\Objetivo\ObjetivoService;
use App\Services\Renda\RendaGeracaoService;
use App\Support\Dashboard\DashboardCache;
use App\Support\Data\FormatarDia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private ?array $contasCarregadas = null;

    private ?array $cartoesCarregados = null;

    public function __construct(
        private readonly LancamentoRepository $lancamentoRepository,
        private readonly ContaBancariaRepository $contaBancariaRepository,
        private readonly CartaoCreditoRepository $cartaoCreditoRepository,
        private readonly FaturaCartaoRepository $faturaCartaoRepository,
        private readonly OrcamentoRepository $orcamentoRepository,
        private readonly ObjetivoService $objetivoService,
        private readonly RecorrenciaService $recorrenciaService,
        private readonly RendaGeracaoService $rendaGeracaoService,
    ) {}

    public function obterDados(int $idUsuario, array $widgets, PeriodoDashboard $periodo = PeriodoDashboard::Atual, ?Carbon $referencia = null): array
    {
        $referencia = $referencia ?? Carbon::today();
        $ano = (int) $referencia->year;
        $mes = (int) $referencia->month;

        $widgetsUnicos = array_values(array_unique($widgets));
        sort($widgetsUnicos);

        $chave = DashboardCache::chave($idUsuario, $ano, $mes, $periodo->value, $widgetsUnicos);

        return Cache::remember($chave, DashboardCache::TTL_SEGUNDOS, function () use (
            $idUsuario,
            $widgetsUnicos,
            $periodo,
            $referencia,
            $ano,
            $mes,
        ): array {
            $this->contasCarregadas = null;
            $this->cartoesCarregados = null;

            $this->gerarLancamentosNecessarios($idUsuario, $widgetsUnicos, $periodo, $referencia);

            $payload = [
                'referencia' => [
                    'ano' => $ano,
                    'mes' => $mes,
                    'rotulo' => $this->rotuloMes($referencia),
                ],
                'periodo' => $periodo->value,
                'widgets' => $widgetsUnicos,
            ];

            foreach ($widgetsUnicos as $widget) {
                $payload[$widget] = match ($widget) {
                    WidgetDashboard::Resumo->value => $this->montarResumo($idUsuario, $referencia),
                    WidgetDashboard::Contas->value => $this->montarContas($idUsuario),
                    WidgetDashboard::Cartoes->value => $this->montarCartoes($idUsuario, $referencia),
                    WidgetDashboard::Categorias->value => $this->montarCategorias($idUsuario, $referencia),
                    WidgetDashboard::ReceitasDespesas->value => $this->montarReceitasDespesas(
                        $idUsuario,
                        $periodo,
                        $referencia,
                    ),
                    WidgetDashboard::Metas->value => $this->montarMetas($idUsuario),
                    default => null,
                };
            }

            return $payload;
        });
    }

    private function gerarLancamentosNecessarios(int $idUsuario, array $widgets, PeriodoDashboard $periodo, Carbon $referencia): void
    {
        $meses = [];
        $exibeReceitasDespesas = in_array(WidgetDashboard::ReceitasDespesas->value, $widgets, true);

        $precisaMesAtual = (bool) array_intersect($widgets, [
            WidgetDashboard::Resumo->value,
            WidgetDashboard::Categorias->value,
            WidgetDashboard::ReceitasDespesas->value,
        ]);

        if ($precisaMesAtual) {
            $meses[] = $referencia->copy()->startOfMonth();
        }

        if ($exibeReceitasDespesas && $periodo !== PeriodoDashboard::Atual) {
            $meses[] = $referencia->copy()->subMonthNoOverflow()->startOfMonth();
        }

        if ($exibeReceitasDespesas && $periodo === PeriodoDashboard::TresMeses) {
            $meses[] = $referencia->copy()->subMonthsNoOverflow(2)->startOfMonth();
        }

        $vistos = [];

        foreach ($meses as $mesRef) {
            $chave = $mesRef->format('Y-m');
            if (isset($vistos[$chave])) {
                continue;
            }
            $vistos[$chave] = true;

            $this->recorrenciaService->gerarParaMes($idUsuario, (int) $mesRef->year, (int) $mesRef->month);
            $this->rendaGeracaoService->gerarParaMes($idUsuario, (int) $mesRef->year, (int) $mesRef->month);
        }
    }

    private function montarResumo(int $idUsuario, Carbon $referencia): array
    {
        $contas = collect($this->obterContasDoUsuario($idUsuario));
        $cartoes = collect($this->obterCartoesDoUsuario($idUsuario, $referencia));

        $dinheiroDisponivel = round((float) $contas->sum('saldo_disponivel_numero'), 2);
        $dinheiroGuardado = round((float) $contas->sum('saldo_guardado_numero'), 2);
        $limiteTotalConfigurado = round((float) $cartoes->sum('limite_total_numero'), 2);
        $limiteLivre = round((float) $cartoes->sum('limite_disponivel_numero'), 2);
        $faturasEmAberto = round((float) $cartoes->sum('fatura_atual_numero'), 2);
        $percentualLivre = $limiteTotalConfigurado > 0
            ? round(($limiteLivre / $limiteTotalConfigurado) * 100, 1)
            : 0.0;

        return [
            'dinheiro_disponivel' => $this->moeda($dinheiroDisponivel),
            'dinheiro_disponivel_numero' => $dinheiroDisponivel,
            'dinheiro_guardado' => $this->moeda($dinheiroGuardado),
            'dinheiro_guardado_numero' => $dinheiroGuardado,
            'limite_livre_cartoes' => $this->moeda($limiteLivre),
            'limite_livre_cartoes_numero' => $limiteLivre,
            'limite_total_configurado' => $this->moeda($limiteTotalConfigurado),
            'limite_total_configurado_numero' => $limiteTotalConfigurado,
            'percentual_livre' => $percentualLivre,
            'faturas_em_aberto' => $this->moeda($faturasEmAberto),
            'faturas_em_aberto_numero' => $faturasEmAberto,
            'proximo_dia_vencimento' => $this->proximoDiaVencimento($cartoes->all(), $referencia),
        ];
    }

    private function proximoDiaVencimento(array $cartoes, Carbon $referencia): ?string
    {
        $cartoesComFatura = array_filter($cartoes, fn (array $cartao) => $cartao['fatura_atual_numero'] > 0);
        $candidatos = $cartoesComFatura ?: $cartoes;

        $proximaData = null;

        foreach ($candidatos as $cartao) {
            $dia = (int) $cartao['dia_vencimento'];
            if ($dia <= 0) {
                continue;
            }

            $data = $referencia->copy()->startOfMonth()->day(min($dia, $referencia->daysInMonth));
            if ($data->lt($referencia->copy()->startOfDay())) {
                $proximoMes = $referencia->copy()->startOfMonth()->addMonthNoOverflow();
                $data = $proximoMes->day(min($dia, $proximoMes->daysInMonth));
            }

            if ($proximaData === null || $data->lt($proximaData)) {
                $proximaData = $data;
            }
        }

        return $proximaData ? FormatarDia::pad($proximaData->day) : null;
    }

    private function montarContas(int $idUsuario): array
    {
        $itens = $this->obterContasDoUsuario($idUsuario);
        $total = round((float) collect($itens)->sum('saldo_disponivel_numero'), 2);

        return [
            'itens' => $itens,
            'total' => [
                'saldo_disponivel' => $this->moeda($total),
                'saldo_disponivel_numero' => $total,
            ],
        ];
    }

    private function obterContasDoUsuario(int $idUsuario): array
    {
        return $this->contasCarregadas ??= $this->contaBancariaRepository
            ->listarParaDashboard($idUsuario)
            ->map(function ($conta) {
                $saldoDisponivel = round((float) ($conta->saldo_disponivel ?? 0), 2);
                $saldoGuardado = round((float) ($conta->saldo_guardado ?? 0), 2);

                return [
                    'id' => (int) $conta->id_conta_bancaria,
                    'nome' => $conta->nome,
                    'tipo' => $conta->tipo?->value,
                    'tipo_rotulo' => $conta->tipo?->rotulo(),
                    'logo' => BancosSugeridos::logoPorNome($conta->nome),
                    'saldo_disponivel' => $this->moeda($saldoDisponivel),
                    'saldo_disponivel_numero' => $saldoDisponivel,
                    'saldo_guardado_numero' => $saldoGuardado,
                ];
            })
            ->values()
            ->all();
    }

    private function montarCartoes(int $idUsuario, Carbon $referencia): array
    {
        return ['itens' => $this->obterCartoesDoUsuario($idUsuario, $referencia)];
    }

    private function obterCartoesDoUsuario(int $idUsuario, Carbon $referencia): array
    {
        if ($this->cartoesCarregados !== null) {
            return $this->cartoesCarregados;
        }

        $proximo = $referencia->copy()->addMonthNoOverflow();

        return $this->cartoesCarregados = $this->cartaoCreditoRepository
            ->listarParaDashboard($idUsuario)
            ->map(function ($cartao) use ($referencia, $proximo) {
                $idCartao = (int) $cartao->id_cartao_credito;
                $faturaAtual = $this->faturaCartaoRepository->valorPorCompetencia(
                    $idCartao,
                    (int) $referencia->year,
                    (int) $referencia->month,
                );
                $faturaProxima = $this->faturaCartaoRepository->valorPorCompetencia(
                    $idCartao,
                    (int) $proximo->year,
                    (int) $proximo->month,
                );

                return [
                    'id' => $idCartao,
                    'nome' => $cartao->nome,
                    'bandeira' => $cartao->bandeira?->value,
                    'bandeira_rotulo' => $cartao->bandeira?->rotulo(),
                    'logo' => BancosSugeridos::logoPorNome($cartao->nome),
                    'dia_vencimento' => (int) $cartao->dia_vencimento,
                    'dia_vencimento_formatado' => FormatarDia::pad($cartao->dia_vencimento),
                    'limite_total' => $this->moeda((float) $cartao->limite_total),
                    'limite_total_numero' => round((float) $cartao->limite_total, 2),
                    'limite_usado' => $this->moeda((float) $cartao->limite_usado),
                    'limite_usado_numero' => round((float) $cartao->limite_usado, 2),
                    'limite_disponivel' => $this->moeda((float) $cartao->limite_disponivel),
                    'limite_disponivel_numero' => round((float) $cartao->limite_disponivel, 2),
                    'percentual_utilizado' => (float) $cartao->percentual_utilizado,
                    'fatura_atual' => $this->moeda((float) $faturaAtual),
                    'fatura_atual_numero' => round((float) $faturaAtual, 2),
                    'fatura_proxima' => $this->moeda((float) $faturaProxima),
                    'fatura_proxima_numero' => round((float) $faturaProxima, 2),
                ];
            })
            ->values()
            ->all();
    }

    private function montarCategorias(int $idUsuario, Carbon $referencia): array
    {
        $despesas = $this->lancamentoRepository->despesasAgrupadasPorCategoriaNoMes(
            $idUsuario,
            (int) $referencia->year,
            (int) $referencia->month,
        );

        $limites = $this->orcamentoRepository
            ->listarPorUsuario($idUsuario, TipoOrcamento::PorCategoria)
            ->keyBy(fn (Orcamento $orcamento) => (int) $orcamento->id_categoria);

        $total = round((float) $despesas->sum('valor'), 2);

        $itens = $despesas->map(function (object $linha) use ($limites, $total) {
            $valor = (float) $linha->valor;
            $orcamento = $limites->get($linha->id_categoria);
            $limite = $orcamento ? (float) $orcamento->valor_mensal : null;
            $percentual = ($limite !== null && $limite > 0)
                ? round(($valor / $limite) * 100, 1)
                : null;

            return [
                'id_categoria' => $linha->id_categoria,
                'nome' => $linha->nome,
                'cor' => $linha->cor,
                'icone' => $linha->icone,
                'valor' => $this->moeda($valor),
                'valor_numero' => $valor,
                'percentual_participacao' => $this->percentualDe($valor, $total),
                'limite' => $limite !== null ? $this->moeda($limite) : null,
                'limite_numero' => $limite,
                'percentual' => $percentual,
                'ultrapassado' => $percentual !== null && $percentual > 100,
            ];
        })->values()->all();

        return [
            'itens' => $itens,
            'total' => $this->moeda($total),
            'total_numero' => $total,
        ];
    }

    private function montarReceitasDespesas(int $idUsuario, PeriodoDashboard $periodo, Carbon $referencia): array
    {
        $meses = match ($periodo) {
            PeriodoDashboard::Atual => [$referencia->copy()->startOfMonth()],
            PeriodoDashboard::Anterior => [$referencia->copy()->subMonthNoOverflow()->startOfMonth()],
            PeriodoDashboard::TresMeses => [
                $referencia->copy()->subMonthsNoOverflow(2)->startOfMonth(),
                $referencia->copy()->subMonthNoOverflow()->startOfMonth(),
                $referencia->copy()->startOfMonth(),
            ],
        };

        $series = [];

        foreach ($meses as $mesRef) {
            $totais = $this->lancamentoRepository->totaisDoMes(
                $idUsuario,
                (int) $mesRef->year,
                (int) $mesRef->month,
            );

            $series[] = [
                'ano' => (int) $mesRef->year,
                'mes' => (int) $mesRef->month,
                'rotulo' => $this->rotuloMesCurto($mesRef),
                'receitas' => round((float) $totais['receitas'], 2),
                'despesas' => round((float) $totais['despesas'], 2),
            ];
        }

        return [
            'series' => $series,
            'totais' => $this->totaisDoPeriodo($series),
        ];
    }

    private function totaisDoPeriodo(array $series): array
    {
        $receitas = round((float) array_sum(array_column($series, 'receitas')), 2);
        $despesas = round((float) array_sum(array_column($series, 'despesas')), 2);
        $saldoPoupado = round($receitas - $despesas, 2);

        $percentualComprometido = $receitas > 0
            ? $this->percentualDe($despesas, $receitas)
            : ($despesas > 0 ? 100.0 : 0.0);

        return [
            'receitas' => $this->moeda($receitas),
            'receitas_numero' => $receitas,
            'despesas' => $this->moeda($despesas),
            'despesas_numero' => $despesas,
            'saldo_poupado' => $this->moeda(abs($saldoPoupado)),
            'saldo_poupado_numero' => $saldoPoupado,
            'percentual_comprometido' => $percentualComprometido,
            'percentual_guardado' => $this->percentualDe(max(0, $saldoPoupado), $receitas),
        ];
    }

    private function montarMetas(int $idUsuario): array
    {
        $itens = $this->objetivoService
            ->listarParaDashboard($idUsuario)
            ->map(fn ($objetivo) => [
                'id' => (int) $objetivo->id_objetivo,
                'descricao' => $objetivo->descricao,
                'valor_meta' => $this->moeda((float) $objetivo->valor_meta),
                'valor_meta_numero' => round((float) $objetivo->valor_meta, 2),
                'valor_guardado' => $this->moeda((float) ($objetivo->valor_guardado ?? 0)),
                'valor_guardado_numero' => round((float) ($objetivo->valor_guardado ?? 0), 2),
                'percentual_atual' => (float) ($objetivo->percentual_atual ?? 0),
                'deposito_mensal_sugerido' => $this->moeda((float) ($objetivo->deposito_mensal_sugerido ?? 0)),
                'deposito_mensal_sugerido_numero' => round((float) ($objetivo->deposito_mensal_sugerido ?? 0), 2),
                'situacao_ritmo' => $objetivo->situacao_ritmo ?? null,
                'situacao_ritmo_rotulo' => $objetivo->situacao_ritmo_rotulo ?? null,
            ])
            ->values()
            ->all();

        return [
            'itens' => $itens,
            'total_objetivos' => count($itens),
        ];
    }

    private function percentualDe(float $parte, float $total): float
    {
        return $total > 0 ? round(($parte / $total) * 100, 1) : 0.0;
    }

    private function moeda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }

    private function rotuloMes(Carbon $data): string
    {
        return ucfirst($data->copy()->locale('pt_BR')->translatedFormat('F')) . '/' . $data->year;
    }

    private function rotuloMesCurto(Carbon $data): string
    {
        return ucfirst($data->copy()->locale('pt_BR')->translatedFormat('M/Y'));
    }
}
