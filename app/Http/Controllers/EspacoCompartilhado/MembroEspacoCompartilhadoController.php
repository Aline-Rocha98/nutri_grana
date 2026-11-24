<?php

namespace App\Http\Controllers\EspacoCompartilhado;

use App\Enum\TipoCategoria;
use App\Http\Controllers\Concerns\RedirecionaErrosDeValidacao;
use App\Http\Controllers\Concerns\RethrowsAuthorizationExceptions;
use App\Http\Controllers\Controller;
use App\Http\Resources\Categoria\CategoriaResource;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\MembroEspacoCompartilhado;
use App\Services\Categoria\CategoriaService;
use App\Services\EspacoCompartilhado\MembroEspacoCompartilhadoService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MembroEspacoCompartilhadoController extends Controller
{
    use AuthorizesRequests;
    use RedirecionaErrosDeValidacao;
    use RethrowsAuthorizationExceptions;

    public function __construct(
        private readonly MembroEspacoCompartilhadoService $membroEspacoService,
        private readonly CategoriaService $categoriaService,
    ) {}

    public function listarCategoriasDoMembro(
        EspacoCompartilhado $espacoCompartilhado,
        MembroEspacoCompartilhado $membroEspaco,
    ): JsonResponse {
        $this->authorize('view', $espacoCompartilhado);

        if ((int) $membroEspaco->id_espaco_compartilhado !== (int) $espacoCompartilhado->id_espaco_compartilhado
            || ! $membroEspaco->estaAtivo()) {
            abort(404);
        }

        $categorias = $this->categoriaService
            ->listarPorUsuario((int) $membroEspaco->id_usuario)
            ->filter(fn ($categoria) => $categoria->tipo === TipoCategoria::Saida)
            ->values();

        return response()->json([
            'categorias' => CategoriaResource::collection($categorias)->resolve(),
        ]);
    }

    public function removerMembroDoEspaco(EspacoCompartilhado $espacoCompartilhado, MembroEspacoCompartilhado $membroEspaco): RedirectResponse
    {
        $this->authorize('gerenciarMembros', $espacoCompartilhado);

        try {
            DB::beginTransaction();
            $this->membroEspacoService->removerMembroDoEspaco($espacoCompartilhado, $membroEspaco, (int) Auth::id());
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Membro removido do espaço.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComPrimeiroErro($e);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao remover membro.');
        }
    }

    public function sairDoEspacoCompartilhado(EspacoCompartilhado $espacoCompartilhado): RedirectResponse
    {
        $this->authorize('view', $espacoCompartilhado);

        $respostaAutorizacao = Gate::inspect('sair', $espacoCompartilhado);

        if ($respostaAutorizacao->denied()) {
            return redirect()
                ->back()
                ->with('erro', $respostaAutorizacao->message());
        }

        try {
            DB::beginTransaction();
            $this->membroEspacoService->sairDoEspaco($espacoCompartilhado, (int) Auth::id());
            DB::commit();

            return redirect()
                ->route('compartilhados.index')
                ->with('sucesso', 'Você saiu do espaço compartilhado.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComPrimeiroErro($e);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao sair do espaço.');
        }
    }
}
