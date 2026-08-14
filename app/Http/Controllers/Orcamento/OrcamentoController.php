<?php

namespace App\Http\Controllers\Orcamento;

use App\Enum\TipoOrcamento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orcamento\AtualizarOrcamentoRequest;
use App\Http\Requests\Orcamento\CriarOrcamentoRequest;
use App\Http\Resources\Categoria\CategoriaResource;
use App\Http\Resources\Orcamento\OrcamentoResource;
use App\Models\Orcamento\Orcamento;
use App\Services\Categoria\CategoriaService;
use App\Services\Orcamento\OrcamentoService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrcamentoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly OrcamentoService $orcamentoService,
        private readonly CategoriaService $categoriaService,
    ) {}

    public function index(?int $ano = null, ?int $mes = null): Response
    {
        $this->authorize('viewAny', Orcamento::class);

        $hoje = now();
        $ano = $ano ?? (int) $hoje->year;
        $mes = $mes ?? (int) $hoje->month;

        if ($mes < 1 || $mes > 12 || $ano < 2000 || $ano > 2100) {
            abort(404);
        }

        $idUsuario = (int) Auth::id();
        $referencia = Carbon::create($ano, $mes, 1)->startOfDay();

        $orcamentos = $this->orcamentoService->listarDoUsuario(
            $idUsuario,
            TipoOrcamento::PorCategoria,
            $referencia,
        );
        $categorias = $this->categoriaService->listarPorUsuario($idUsuario)
            ->filter(fn ($categoria) => $categoria->tipo?->value === 'saida'
                && $categoria->arquivada?->value !== 'S');

        return Inertia::render('Orcamento/Index', [
            'ano' => $ano,
            'mes' => $mes,
            'orcamentos' => OrcamentoResource::collection($orcamentos)->resolve(),
            'categorias' => CategoriaResource::collection($categorias)->resolve(),
            'tiposOrcamento' => TipoOrcamento::opcoesParaSelect(),
            'tipoAtivo' => TipoOrcamento::PorCategoria->value,
            'urlBase' => url('/orcamentos'),
        ]);
    }

    public function criar(CriarOrcamentoRequest $request): RedirectResponse
    {
        $this->authorize('create', Orcamento::class);

        try {
            DB::beginTransaction();
            $this->orcamentoService->criar((int) Auth::id(), $request->validated());
            DB::commit();

            return $this->redirecionarParaIndex($request)
                ->with('sucesso', 'Orçamento criado com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('erro', 'Erro ao criar orçamento.')
                ->withInput();
        }
    }

    public function atualizar(AtualizarOrcamentoRequest $request, Orcamento $orcamento): RedirectResponse
    {
        $this->authorize('update', $orcamento);

        try {
            DB::beginTransaction();
            $this->orcamentoService->atualizar($orcamento, (int) Auth::id(), $request->validated());
            DB::commit();

            return $this->redirecionarParaIndex($request)
                ->with('sucesso', 'Orçamento atualizado com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('erro', 'Erro ao atualizar orçamento.');
        }
    }

    public function excluir(Request $request, Orcamento $orcamento): RedirectResponse
    {
        $this->authorize('delete', $orcamento);

        try {
            DB::beginTransaction();
            $this->orcamentoService->excluir($orcamento, (int) Auth::id());
            DB::commit();

            return $this->redirecionarParaIndex($request)
                ->with('sucesso', 'Orçamento excluído com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarParaIndex($request)
                ->with('erro', collect($e->errors())->flatten()->first());
        } catch (Exception $e) {
            DB::rollBack();

            return $this->redirecionarParaIndex($request)
                ->with('erro', 'Erro ao excluir orçamento.');
        }
    }

    private function redirecionarParaIndex(Request $request): RedirectResponse
    {
        $hoje = now();
        $ano = (int) ($request->input('ano') ?: $hoje->year);
        $mes = (int) ($request->input('mes') ?: $hoje->month);

        if ($mes < 1 || $mes > 12 || $ano < 2000 || $ano > 2100) {
            $ano = (int) $hoje->year;
            $mes = (int) $hoje->month;
        }

        return redirect()->route('orcamentos.index', [
            'ano' => $ano,
            'mes' => $mes,
        ]);
    }
}
