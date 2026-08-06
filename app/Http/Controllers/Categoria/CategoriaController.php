<?php

namespace App\Http\Controllers\Categoria;

use App\Data\IconesCategoria;
use App\Enum\SimNao;
use App\Enum\TipoCategoria;
use App\Http\Controllers\Controller;
use App\Http\Requests\Categoria\ArquivarCategoriaRequest;
use App\Http\Requests\Categoria\AtualizarCategoriaRequest;
use App\Http\Requests\Categoria\CriarCategoriaRequest;
use App\Http\Resources\Categoria\CategoriaResource;
use App\Models\Categoria\Categoria;
use App\Services\Categoria\CategoriaService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CategoriaService $categoriaService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Categoria::class);

        $categorias = $this->categoriaService->listarPorUsuario((int) Auth::id());

        return Inertia::render('Categoria/Index', [
            'categorias' => CategoriaResource::collection($categorias)->resolve(),
            'tipos' => TipoCategoria::opcoesParaSelect(),
            'icones' => IconesCategoria::opcoesParaSelect(),
        ]);
    }

    public function criarCategoria(CriarCategoriaRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $this->categoriaService->criar((int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('categorias.index')
                ->with('sucesso', 'Categoria criada com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('categorias.index')
                ->withErrors($e->errors());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('categorias.index')
                ->with('erro', 'Erro ao criar categoria.');
        }
    }

    public function atualizarCategoria(AtualizarCategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $this->categoriaService->atualizar($categoria, (int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('categorias.index')
                ->with('sucesso', 'Categoria atualizada com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('categorias.index')
                ->withErrors($e->errors());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('categorias.index')
                ->with('erro', 'Erro ao atualizar categoria.');
        }
    }

    public function arquivarCategoria(ArquivarCategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $arquivada = SimNao::from($request->validated('arquivada'));
            $this->categoriaService->arquivar(
                $categoria,
                (int) Auth::id(),
                $arquivada
            );
            DB::commit();

            $mensagem = $arquivada === SimNao::Sim
                ? 'Categoria arquivada com sucesso.'
                : 'Categoria desarquivada com sucesso.';

            return redirect()
                ->route('categorias.index')
                ->with('sucesso', $mensagem);
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('categorias.index')
                ->with('erro', 'Erro ao arquivar categoria.');
        }
    }

    public function excluirCategoria(Categoria $categoria): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $this->categoriaService->excluir($categoria, (int) Auth::id());
            DB::commit();

            return redirect()
                ->route('categorias.index')
                ->with('sucesso', 'Categoria excluída com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('categorias.index')
                ->withErrors($e->errors());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('categorias.index')
                ->with('erro', 'Erro ao excluir categoria.');
        }
    }
}
