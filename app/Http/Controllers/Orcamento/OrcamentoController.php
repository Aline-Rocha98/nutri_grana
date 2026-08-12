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
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
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

    public function index(): Response
    {
        $this->authorize('viewAny', Orcamento::class);

        $idUsuario = (int) Auth::id();
        $orcamentos = $this->orcamentoService->listarDoUsuario(
            $idUsuario,
            TipoOrcamento::PorCategoria,
        );
        $categorias = $this->categoriaService->listarPorUsuario($idUsuario)
            ->filter(fn ($categoria) => $categoria->tipo?->value === 'saida'
                && $categoria->arquivada?->value !== 'S');

        return Inertia::render('Orcamento/Index', [
            'orcamentos' => OrcamentoResource::collection($orcamentos)->resolve(),
            'categorias' => CategoriaResource::collection($categorias)->resolve(),
            'tiposOrcamento' => TipoOrcamento::opcoesParaSelect(),
            'tipoAtivo' => TipoOrcamento::PorCategoria->value,
        ]);
    }

    public function criar(CriarOrcamentoRequest $request): RedirectResponse
    {
        $this->authorize('create', Orcamento::class);

        try {
            DB::beginTransaction();
            $this->orcamentoService->criar((int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('orcamentos.index')
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

            return redirect()
                ->route('orcamentos.index')
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

    public function excluir(Orcamento $orcamento): RedirectResponse
    {
        $this->authorize('delete', $orcamento);

        try {
            DB::beginTransaction();
            $this->orcamentoService->excluir($orcamento, (int) Auth::id());
            DB::commit();

            return redirect()
                ->route('orcamentos.index')
                ->with('sucesso', 'Orçamento excluído com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('orcamentos.index')
                ->with('erro', collect($e->errors())->flatten()->first());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('orcamentos.index')
                ->with('erro', 'Erro ao excluir orçamento.');
        }
    }
}
