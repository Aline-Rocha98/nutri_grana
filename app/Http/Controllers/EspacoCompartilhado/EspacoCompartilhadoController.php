<?php

namespace App\Http\Controllers\EspacoCompartilhado;

use App\Enum\FrequenciaRecorrencia;
use App\Enum\SituacaoLancamento;
use App\Enum\StatusLancamentoCompartilhado;
use App\Http\Controllers\Concerns\RedirecionaErrosDeValidacao;
use App\Http\Controllers\Concerns\RethrowsAuthorizationExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\EspacoCompartilhado\AtualizarEspacoCompartilhadoRequest;
use App\Http\Requests\EspacoCompartilhado\CriarEspacoCompartilhadoRequest;
use App\Http\Resources\CartaoCredito\CartaoCreditoResource;
use App\Http\Resources\Categoria\CategoriaResource;
use App\Http\Resources\ContaBancaria\ContaBancariaResource;
use App\Http\Resources\EspacoCompartilhado\AuditoriaEspacoCompartilhadoResource;
use App\Http\Resources\EspacoCompartilhado\ConviteEspacoResource;
use App\Http\Resources\EspacoCompartilhado\ConviteRecebidoResource;
use App\Http\Resources\EspacoCompartilhado\EspacoCompartilhadoResource;
use App\Http\Resources\EspacoCompartilhado\LancamentoCompartilhadoResource;
use App\Http\Resources\EspacoCompartilhado\MembroEspacoResource;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Services\CartaoCredito\CartaoCreditoService;
use App\Services\Categoria\CategoriaService;
use App\Services\ContaBancaria\ContaBancariaService;
use App\Services\EspacoCompartilhado\AuditoriaEspacoCompartilhadoService;
use App\Services\EspacoCompartilhado\ConviteCompartilhamentoService;
use App\Services\EspacoCompartilhado\EspacoCompartilhadoService;
use App\Services\EspacoCompartilhado\LancamentoCompartilhadoService;
use App\Services\EspacoCompartilhado\MembroEspacoCompartilhadoService;
use App\Services\EspacoCompartilhado\ResumoFinanceiroEspacoService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EspacoCompartilhadoController extends Controller
{
    use AuthorizesRequests;
    use RedirecionaErrosDeValidacao;
    use RethrowsAuthorizationExceptions;

    public function __construct(
        private readonly EspacoCompartilhadoService $espacoService,
        private readonly MembroEspacoCompartilhadoService $membroEspacoService,
        private readonly ConviteCompartilhamentoService $conviteService,
        private readonly LancamentoCompartilhadoService $lancamentoCompartilhadoService,
        private readonly ResumoFinanceiroEspacoService $resumoFinanceiroService,
        private readonly AuditoriaEspacoCompartilhadoService $auditoriaService,
        private readonly ContaBancariaService $contaBancariaService,
        private readonly CartaoCreditoService $cartaoCreditoService,
        private readonly CategoriaService $categoriaService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EspacoCompartilhado::class);

        $usuario = $request->user();
        $espacos = $this->espacoService->listarEspacosDoUsuario((int) $usuario->id_usuario);
        $convitesRecebidos = $this->conviteService->listarConvitesRecebidosPeloUsuario($usuario);

        return Inertia::render('EspacoCompartilhado/Index', [
            'espacos' => EspacoCompartilhadoResource::collection($espacos)->resolve(),
            'convitesRecebidos' => ConviteRecebidoResource::collection($convitesRecebidos)->resolve(),
            'urlCriarEspaco' => route('compartilhados.criar'),
        ]);
    }

    public function show(Request $request, EspacoCompartilhado $espacoCompartilhado, ?int $ano = null, ?int $mes = null): Response
    {
        $this->authorize('view', $espacoCompartilhado);

        $hoje = now();
        $ano = $ano ?? (int) $hoje->year;
        $mes = $mes ?? (int) $hoje->month;

        if ($mes < 1 || $mes > 12 || $ano < 2000 || $ano > 2100) {
            abort(404);
        }

        $idUsuario = (int) Auth::id();
        $statusFiltrado = $this->obterStatusFiltrado($request);
        $membros = $this->membroEspacoService->listarMembrosAtivosDoEspaco($espacoCompartilhado);
        $usuarioEhAdministrador = $membros->contains(
            fn ($membro) => (int) $membro->id_usuario === $idUsuario && $membro->ehAdministrador()
        );
        $lancamentos = $this->lancamentoCompartilhadoService->listarLancamentosDoMes($espacoCompartilhado, $ano, $mes, $statusFiltrado);
        $resumo = $this->resumoFinanceiroService->montarResumoDoMes($espacoCompartilhado, $membros, $idUsuario, $ano, $mes);
        $convitesPendentes = $usuarioEhAdministrador
            ? ConviteEspacoResource::collection($this->conviteService->listarConvitesPendentesDoEspaco($espacoCompartilhado))->resolve()
            : [];

        return Inertia::render('EspacoCompartilhado/Show', [
            'espaco' => (new EspacoCompartilhadoResource($espacoCompartilhado))->resolve(),
            'ano' => $ano,
            'mes' => $mes,
            'filtros' => ['status' => $statusFiltrado],
            'lancamentos' => LancamentoCompartilhadoResource::colecaoComPermissoesDoUsuario($lancamentos, $idUsuario, $usuarioEhAdministrador),
            'resumo' => $resumo,
            'membros' => MembroEspacoResource::collection($membros)->resolve(),
            'usuarioEhAdministrador' => $usuarioEhAdministrador,
            'convitesPendentes' => $convitesPendentes,
            'historico' => Inertia::optional(fn () => AuditoriaEspacoCompartilhadoResource::collection(
                $this->auditoriaService->listarHistoricoDoEspaco((int) $espacoCompartilhado->id_espaco_compartilhado)
            )->resolve()),
            'dadosLancamentoPessoal' => Inertia::optional(fn () => $this->montarDadosDoLancamentoPessoal($idUsuario)),
            'frequencias' => FrequenciaRecorrencia::opcoesParaSelect(),
            'urls' => [
                'base' => route('compartilhados.show', ['espacoCompartilhado' => $espacoCompartilhado]),
                'criarLancamento' => route('compartilhados.lancamentos.criar', ['espacoCompartilhado' => $espacoCompartilhado]),
                'enviarConvite' => route('compartilhados.convites.enviar', ['espacoCompartilhado' => $espacoCompartilhado]),
                'listaEspacos' => route('compartilhados.index'),
            ],
        ]);
    }

    public function criarEspacoCompartilhado(CriarEspacoCompartilhadoRequest $request): RedirectResponse
    {
        $this->authorize('create', EspacoCompartilhado::class);

        try {
            DB::beginTransaction();
            $espaco = $this->espacoService->criarEspacoCompartilhado((int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('compartilhados.show', ['espacoCompartilhado' => $espaco])
                ->with('sucesso', 'Espaço compartilhado criado com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComErrosDeValidacao($e);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao criar espaço compartilhado.')
                ->withInput();
        }
    }

    public function atualizarEspacoCompartilhado(AtualizarEspacoCompartilhadoRequest $request, EspacoCompartilhado $espacoCompartilhado): RedirectResponse
    {
        $this->authorize('update', $espacoCompartilhado);

        try {
            DB::beginTransaction();
            $this->espacoService->atualizarEspacoCompartilhado($espacoCompartilhado, (int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Espaço atualizado com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComErrosDeValidacao($e);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao atualizar espaço.');
        }
    }

    public function arquivarEspacoCompartilhado(EspacoCompartilhado $espacoCompartilhado): RedirectResponse
    {
        $this->authorize('arquivar', $espacoCompartilhado);

        try {
            DB::beginTransaction();
            $this->espacoService->arquivarEspacoCompartilhado($espacoCompartilhado, (int) Auth::id());
            DB::commit();

            return redirect()
                ->route('compartilhados.index')
                ->with('sucesso', 'Espaço arquivado.');
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao arquivar espaço.');
        }
    }

    private function obterStatusFiltrado(Request $request): ?string
    {
        $status = $request->query('status');
        $statusPermitidos = [
            StatusLancamentoCompartilhado::Pendente->value,
            StatusLancamentoCompartilhado::Pago->value,
        ];

        return in_array($status, $statusPermitidos, true) ? $status : null;
    }

    private function montarDadosDoLancamentoPessoal(int $idUsuario): array
    {
        $contas = $this->contaBancariaService->listarPorUsuario($idUsuario)
            ->filter(fn ($conta) => $conta->arquivada?->value !== 'S')
            ->values();
        $cartoes = $this->cartaoCreditoService->listarPorUsuario($idUsuario)
            ->filter(fn ($cartao) => $cartao->arquivada?->value !== 'S')
            ->values();
        $categorias = $this->categoriaService->listarPorUsuario($idUsuario);

        return [
            'contasBancarias' => ContaBancariaResource::collection($contas)->resolve(),
            'cartoesCredito' => CartaoCreditoResource::collection($cartoes)->resolve(),
            'categorias' => CategoriaResource::collection($categorias)->resolve(),
            'situacoes' => SituacaoLancamento::opcoesParaSelect(),
        ];
    }
}
