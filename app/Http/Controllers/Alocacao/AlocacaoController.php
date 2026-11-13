<?php

namespace App\Http\Controllers\Alocacao;

use App\Http\Controllers\Concerns\RethrowsAuthorizationExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Alocacao\AtualizarAlocacaoRequest;
use App\Http\Requests\Alocacao\CriarAlocacaoRequest;
use App\Http\Requests\Alocacao\ResgatarAlocacaoRequest;
use App\Http\Requests\Alocacao\TransferirEntreAlocacoesRequest;
use App\Http\Resources\Alocacao\AlocacaoCadastradaResource;
use App\Http\Resources\Alocacao\AlocacaoResource;
use App\Models\Alocacao\Alocacao;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Services\Alocacao\AlocacaoService;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AlocacaoController extends Controller
{
    use AuthorizesRequests;
    use RethrowsAuthorizationExceptions;

    public function __construct(
        private readonly AlocacaoService $alocacaoService,
        private readonly ContaBancariaRepository $contaBancariaRepository,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Alocacao::class);

        $idUsuario = (int) Auth::id();
        $alocacoes = $this->alocacaoService->listarAlocacoesComSaldoDoUsuario($idUsuario);

        $contasBancarias = $this->contaBancariaRepository->listarOpcoesAtivasDoUsuario($idUsuario)
            ->map(fn ($conta) => [
                'id' => $conta->id_conta_bancaria,
                'nome' => $conta->nome,
            ])
            ->values();

        return Inertia::render('Alocacao/Index', [
            'alocacoes' => AlocacaoResource::collection($alocacoes)->resolve(),
            'alocacoesCadastradas' => fn () => $this->montarAlocacoesCadastradasPaginadas(
                $this->alocacaoService->paginarAlocacoesCadastradas($idUsuario),
            ),
            'totalAlocado' => round((float) $alocacoes->sum('saldo_consolidado'), 2),
            'contasBancarias' => $contasBancarias,
            'urlCriar' => route('alocacoes.criar'),
        ]);
    }

    public function criarAlocacao(CriarAlocacaoRequest $request): RedirectResponse
    {
        $this->authorize('create', Alocacao::class);

        try {
            DB::beginTransaction();
            $this->alocacaoService->criarAlocacao((int) Auth::id(), $request->validated('nome'));
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Alocação criada com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao criar alocação.');
        }
    }

    public function atualizarAlocacao(AtualizarAlocacaoRequest $request, Alocacao $alocacao): RedirectResponse
    {
        $this->authorize('update', $alocacao);

        try {
            DB::beginTransaction();
            $this->alocacaoService->renomearAlocacao($alocacao, (int) Auth::id(), $request->validated('nome'));
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Alocação atualizada com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao atualizar alocação.');
        }
    }

    public function excluirAlocacao(Alocacao $alocacao): RedirectResponse
    {
        $this->authorize('delete', $alocacao);

        try {
            DB::beginTransaction();
            $this->alocacaoService->excluirAlocacao($alocacao, (int) Auth::id());
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Alocação excluída com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('erro', collect($e->errors())->flatten()->first());
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao excluir alocação.');
        }
    }

    public function transferirEntreAlocacoes(TransferirEntreAlocacoesRequest $request, Alocacao $alocacao): RedirectResponse
    {
        $this->authorize('update', $alocacao);

        try {
            DB::beginTransaction();
            $this->alocacaoService->transferirEntreAlocacoes($alocacao, (int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('alocacoes.index')
                ->with('sucesso', 'Transferência entre alocações realizada com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->route('alocacoes.index')
                ->with('erro', 'Erro ao transferir entre alocações.');
        }
    }

    public function resgatarAlocacao(ResgatarAlocacaoRequest $request, Alocacao $alocacao): RedirectResponse
    {
        $this->authorize('update', $alocacao);

        try {
            DB::beginTransaction();
            $this->alocacaoService->resgatarAlocacaoParaConta($alocacao, (int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('alocacoes.index')
                ->with('sucesso', 'Resgate realizado com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->route('alocacoes.index')
                ->with('erro', 'Erro ao resgatar da alocação.');
        }
    }

    private function montarAlocacoesCadastradasPaginadas(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => AlocacaoCadastradaResource::collection($paginator->getCollection())->resolve(),
            'links' => $paginator->linkCollection()->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
