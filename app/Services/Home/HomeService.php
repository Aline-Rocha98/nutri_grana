<?php

namespace App\Services\Home;

use App\Enum\SimNao;
use App\Enum\SituacaoRitmoObjetivo;
use App\Repositories\CartaoCredito\CartaoCreditoRepository;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Services\Financeiro\ConsultaFinanceiraUsuarioService;
use App\Services\Lancamento\RecorrenciaService;
use App\Services\Objetivo\ObjetivoService;
use App\Services\Renda\RendaGeracaoService;
use App\Support\Dashboard\DashboardCache;
use App\Support\Data\FormatarDia;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HomeService
{
    public const DIAS_JANELA_COMPROMISSOS = 7;

    private const LIMITE_COMPROMISSOS = 6;

    private const DESCRICOES_NO_RESUMO_A_PAGAR = 2;

    private const ICONE_PADRAO_COMPROMISSO = 'receipt_long';

    private const COR_PADRAO_COMPROMISSO = '#f59e0b';

    public function __construct(
        private readonly ContaBancariaRepository $contaBancariaRepository,
        private readonly CartaoCreditoRepository $cartaoCreditoRepository,
        private readonly ObjetivoService $objetivoService,
        private readonly RecorrenciaService $recorrenciaService,
        private readonly RendaGeracaoService $rendaGeracaoService,
        private readonly MontadorDicaFinanceira $montadorDicaFinanceira,
        private readonly ConsultaFinanceiraUsuarioService $consultaFinanceiraUsuarioService,
    ) {}

    public function obterResumoDoDia(int $idUsuario, ?Carbon $hoje = null): array
    {
        $hoje = ($hoje ?? Carbon::today())->copy()->startOfDay();

        $chave = sprintf(
            'home:%d:%d:%s',
            $idUsuario,
            DashboardCache::versao($idUsuario),
            $hoje->toDateString(),
        );

        return Cache::remember($chave, DashboardCache::TTL_SEGUNDOS, function () use ($idUsuario, $hoje): array {
            $fimDaJanela = $hoje->copy()->addDays(self::DIAS_JANELA_COMPROMISSOS);

            $this->gerarLancamentosDaJanela($idUsuario, $hoje, $fimDaJanela);

            $contas = $this->contaBancariaRepository->listarParaDashboard($idUsuario);
            $compromissos = $this->montarCompromissosPendentes($idUsuario, $hoje, $fimDaJanela);

            return [
                'dias_janela' => self::DIAS_JANELA_COMPROMISSOS,
                'saldo_livre' => $this->montarSaldoLivre($contas),
                'a_pagar' => $this->montarResumoAPagar($idUsuario, $fimDaJanela, $compromissos),
                'compromissos' => $compromissos,
                'cartao_preferencial' => $this->montarCartaoPreferencial($idUsuario),
                'meta_em_destaque' => $this->montarMetaEmDestaque($idUsuario),
                'dica_financeira' => $this->montarDicaFinanceira($idUsuario, $contas, $hoje),
            ];
        });
    }

    private function gerarLancamentosDaJanela(int $idUsuario, Carbon $hoje, Carbon $fimDaJanela): void
    {
        $meses = collect([$hoje, $fimDaJanela])
            ->map(fn (Carbon $data) => $data->copy()->startOfMonth())
            ->unique(fn (Carbon $data) => $data->format('Y-m'));

        foreach ($meses as $mesReferencia) {
            $this->recorrenciaService->gerarParaMes($idUsuario, (int) $mesReferencia->year, (int) $mesReferencia->month);
            $this->rendaGeracaoService->gerarParaMes($idUsuario, (int) $mesReferencia->year, (int) $mesReferencia->month);
        }
    }

    private function montarSaldoLivre(Collection $contas): array
    {
        $valor = round((float) $contas->sum('saldo_disponivel'), 2);
        $contaPrincipal = $contas->first(fn ($conta) => $conta->padrao_desconto === SimNao::Sim);

        return [
            'valor' => $this->moeda($valor),
            'valor_numero' => $valor,
            'conta_principal_nome' => $contaPrincipal?->nome,
            'conta_principal_tipo_rotulo' => $contaPrincipal?->tipo?->rotulo(),
        ];
    }

    private function montarResumoAPagar(int $idUsuario, Carbon $fimDaJanela, array $compromissos): array
    {
        $resumo = $this->consultaFinanceiraUsuarioService->resumirPendentesUnificadosAte($idUsuario, $fimDaJanela);

        return [
            'total' => $this->moeda($resumo['total']),
            'total_numero' => $resumo['total'],
            'quantidade' => $resumo['quantidade'],
            'resumo_descricoes' => $this->resumirDescricoes($compromissos, $resumo['quantidade']),
        ];
    }

    private function resumirDescricoes(array $compromissos, int $quantidadeTotal): string
    {
        $descricoes = array_slice(array_column($compromissos, 'descricao'), 0, self::DESCRICOES_NO_RESUMO_A_PAGAR);
        $restantes = $quantidadeTotal - count($descricoes);

        if ($descricoes === []) {
            return '';
        }

        if ($restantes > 0) {
            return implode(', ', $descricoes) . " e mais {$restantes}";
        }

        return count($descricoes) > 1
            ? implode(' e ', $descricoes)
            : $descricoes[0];
    }

    private function montarCompromissosPendentes(int $idUsuario, Carbon $hoje, Carbon $fimDaJanela): array
    {
        return $this->consultaFinanceiraUsuarioService
            ->listarPendentesUnificadosAte(
                $idUsuario,
                $hoje,
                $fimDaJanela,
                self::LIMITE_COMPROMISSOS,
                self::ICONE_PADRAO_COMPROMISSO,
                self::COR_PADRAO_COMPROMISSO,
            )
            ->map(fn (array $item) => [
                ...$item,
                'valor' => $this->moeda((float) $item['valor_numero']),
            ])
            ->all();
    }

    private function montarCartaoPreferencial(int $idUsuario): ?array
    {
        $cartao = $this->cartaoCreditoRepository->buscarPreferencialDoUsuario($idUsuario);

        if (! $cartao) {
            return null;
        }

        return [
            'nome' => $cartao->nome,
            'limite_disponivel' => $this->moeda((float) $cartao->limite_disponivel),
            'dia_fechamento_formatado' => FormatarDia::pad($cartao->dia_fechamento),
            'dia_vencimento_formatado' => FormatarDia::pad($cartao->dia_vencimento),
        ];
    }

    private function montarMetaEmDestaque(int $idUsuario): ?array
    {
        $objetivo = $this->objetivoService
            ->listarParaDashboard($idUsuario)
            ->first(fn ($objetivo) => ($objetivo->situacao_ritmo ?? null) !== SituacaoRitmoObjetivo::Concluido->value);

        if (! $objetivo) {
            return null;
        }

        return [
            'descricao' => $objetivo->descricao,
            'data_limite_formatada' => $objetivo->data_limite?->format('d/m/Y'),
            'valor_guardado' => $this->moeda((float) ($objetivo->valor_guardado ?? 0)),
            'valor_meta' => $this->moeda((float) $objetivo->valor_meta),
            'percentual_atual' => (float) ($objetivo->percentual_atual ?? 0),
        ];
    }

    private function montarDicaFinanceira(int $idUsuario, Collection $contas, Carbon $hoje): array
    {
        $totalGuardado = round((float) $contas->sum('saldo_guardado'), 2);
        $receitasDoMes = (float) $this->consultaFinanceiraUsuarioService
            ->totaisDoMes($idUsuario, (int) $hoje->year, (int) $hoje->month)['receitas'];
        $contasFixasDoMes = $this->consultaFinanceiraUsuarioService
            ->somarDespesasRecorrentesDoMes($idUsuario, (int) $hoje->year, (int) $hoje->month);

        $percentualContasFixas = $receitasDoMes > 0
            ? round(($contasFixasDoMes / $receitasDoMes) * 100, 1)
            : null;

        return $this->montadorDicaFinanceira->montarDicaFinanceira($totalGuardado, $percentualContasFixas);
    }

    private function moeda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
