<?php

namespace App\Services\Dashboard;

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
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
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

    
    public function obterDados(int $idUsuario, array $widgets, PeriodoDashboard $periodo = PeriodoDashboard::Atual,?Carbon $referencia = null): array 
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

        $precisaMesAtual = (bool) array_intersect($widgets, [
            WidgetDashboard::Resumo->value,
            WidgetDashboard::Categorias->value,
            WidgetDashboard::ReceitasDespesas->value,
        ]);

        if ($precisaMesAtual) {
            $meses[] = $referencia->copy()->startOfMonth();
        }

        if (in_array(WidgetDashboard::Resumo->value, $widgets, true)
            || ($periodo === PeriodoDashboard::Anterior && in_array(WidgetDashboard::ReceitasDespesas->value, $widgets, true))
            || ($periodo === PeriodoDashboard::TresMeses && in_array(WidgetDashboard::ReceitasDespesas->value, $widgets, true))
        ) {
            $meses[] = $referencia->copy()->subMonthNoOverflow()->startOfMonth();
        }

        if ($periodo === PeriodoDashboard::TresMeses
            && in_array(WidgetDashboard::ReceitasDespesas->value, $widgets, true)
        ) {
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
        $atual = $this->totaisAgregados($idUsuario, (int) $referencia->year, (int) $referencia->month);
        $anteriorRef = $referencia->copy()->subMonthNoOverflow();
        $anterior = $this->totaisAgregados(
            $idUsuario,
            (int) $anteriorRef->year,
            (int) $anteriorRef->month,
        );

        return [
            'receitas_recebidas' => $this->moeda($atual['receitas_pagas']),
            'despesas_pagas' => $this->moeda($atual['despesas_pagas']),
            'saldo' => $this->moeda($atual['saldo_efetivado']),
            'receitas_previstas' => $this->moeda($atual['receitas']),
            'despesas_previstas' => $this->moeda($atual['despesas']),
            'saldo_previsto' => $this->moeda($atual['saldo']),
            'comparacao' => [
                'mes_anterior_rotulo' => $this->rotuloMes($anteriorRef),
                'receitas_recebidas' => $this->variacao($atual['receitas_pagas'], $anterior['receitas_pagas']),
                'despesas_pagas' => $this->variacao($atual['despesas_pagas'], $anterior['despesas_pagas']),
                'saldo' => $this->variacao($atual['saldo_efetivado'], $anterior['saldo_efetivado']),
            ],
            'numeros' => [
                'receitas_recebidas' => $atual['receitas_pagas'],
                'despesas_pagas' => $atual['despesas_pagas'],
                'saldo' => $atual['saldo_efetivado'],
                'receitas_previstas' => $atual['receitas'],
                'despesas_previstas' => $atual['despesas'],
                'saldo_previsto' => $atual['saldo'],
            ],
        ];
    }

    private function totaisAgregados(int $idUsuario, int $ano, int $mes): array
    {
        $totais = $this->lancamentoRepository->totaisDoMes($idUsuario, $ano, $mes);
        $totais['saldo_efetivado'] = round(
            (float) $totais['receitas_pagas'] - (float) $totais['despesas_pagas'],
            2,
        );

        return $totais;
    }

    private function montarContas(int $idUsuario): array
    {
        $contas = $this->contaBancariaRepository->listarParaDashboard($idUsuario);
        $itens = $contas->map(fn ($conta) => [
            'id' => (int) $conta->id_conta_bancaria,
            'nome' => $conta->nome,
            'saldo' => $this->moeda((float) ($conta->saldo_atual ?? 0)),
            'saldo_numero' => round((float) ($conta->saldo_atual ?? 0), 2),
        ])->values()->all();

        $total = round(collect($itens)->sum('saldo_numero'), 2);

        return [
            'itens' => $itens,
            'total' => [
                'saldo' => $this->moeda($total),
                'saldo_numero' => $total,
            ],
        ];
    }

    private function montarCartoes(int $idUsuario, Carbon $referencia): array
    {
        $proximo = $referencia->copy()->addMonthNoOverflow();

        $itens = $this->cartaoCreditoRepository
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
                    'limite_total' => $this->moeda((float) $cartao->limite_total),
                    'limite_total_numero' => round((float) $cartao->limite_total, 2),
                    'limite_usado' => $this->moeda((float) $cartao->limite_usado),
                    'limite_usado_numero' => round((float) $cartao->limite_usado, 2),
                    'limite_disponivel' => $this->moeda((float) $cartao->limite_disponivel),
                    'limite_disponivel_numero' => round((float) $cartao->limite_disponivel, 2),
                    'percentual_utilizado' => (float) $cartao->percentual_utilizado,
                    'fatura_atual' => $this->moeda($faturaAtual),
                    'fatura_atual_numero' => $faturaAtual,
                    'fatura_proxima' => $this->moeda($faturaProxima),
                    'fatura_proxima_numero' => $faturaProxima,
                ];
            })
            ->values()
            ->all();

        return ['itens' => $itens];
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

        $itens = $despesas->map(function (object $linha) use ($limites) {
            $orcamento = $limites->get($linha->id_categoria);
            $limite = $orcamento ? (float) $orcamento->valor_mensal : null;
            $percentual = ($limite !== null && $limite > 0)
                ? round(($linha->valor / $limite) * 100, 1)
                : null;

            return [
                'id_categoria' => $linha->id_categoria,
                'nome' => $linha->nome,
                'cor' => $linha->cor,
                'icone' => $linha->icone,
                'valor' => $this->moeda($linha->valor),
                'valor_numero' => $linha->valor,
                'limite' => $limite !== null ? $this->moeda($limite) : null,
                'limite_numero' => $limite,
                'percentual' => $percentual,
                'percentual_barra' => $percentual !== null ? min(100, $percentual) : null,
                'ultrapassado' => $percentual !== null && $percentual > 100,
            ];
        })->values()->all();

        return ['itens' => $itens];
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

        return ['series' => $series];
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
                'situacao_ritmo' => $objetivo->situacao_ritmo ?? null,
                'situacao_ritmo_rotulo' => $objetivo->situacao_ritmo_rotulo ?? null,
            ])
            ->values()
            ->all();

        return ['itens' => $itens];
    }

    private function variacao(float $atual, float $anterior): array
    {
        $diferenca = round($atual - $anterior, 2);
        $percentual = abs($anterior) > 0.0001
            ? round(($diferenca / abs($anterior)) * 100, 1)
            : ($atual > 0 ? 100.0 : ($atual < 0 ? -100.0 : 0.0));

        $direcao = match (true) {
            $diferenca > 0 => 'alta',
            $diferenca < 0 => 'baixa',
            default => 'igual',
        };

        return [
            'valor_anterior' => round($anterior, 2),
            'valor_anterior_formatado' => $this->moeda($anterior),
            'diferenca' => $diferenca,
            'diferenca_formatada' => $this->moeda(abs($diferenca)),
            'percentual' => $percentual,
            'direcao' => $direcao,
        ];
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
