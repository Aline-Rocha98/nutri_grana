<?php

namespace App\Http\Controllers\Renda;

use App\Enum\FrequenciaRecorrencia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Renda\AtualizarRendaRequest;
use App\Http\Requests\Renda\CriarRendaRequest;
use App\Http\Resources\ContaBancaria\ContaBancariaResource;
use App\Http\Resources\Renda\RendaResource;
use App\Models\Renda\Renda;
use App\Services\ContaBancaria\ContaBancariaService;
use App\Services\Renda\RendaService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RendaController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly RendaService $rendaService,
        private readonly ContaBancariaService $contaBancariaService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Renda::class);

        $idUsuario = (int) Auth::id();
        $rendas = $this->rendaService->listarDoUsuario($idUsuario);
        $contas = $this->contaBancariaService->listarPorUsuario($idUsuario)
            ->filter(fn ($conta) => $conta->arquivada?->value !== 'S');

        return Inertia::render('Renda/Index', [
            'rendas' => RendaResource::collection($rendas)->resolve(),
            'contasBancarias' => ContaBancariaResource::collection($contas)->resolve(),
            'frequencias' => FrequenciaRecorrencia::opcoesParaSelect(),
            'urlCriar' => route('rendas.criar'),
        ]);
    }

    public function criar(CriarRendaRequest $request): RedirectResponse
    {
        $this->authorize('create', Renda::class);

        try {
            DB::beginTransaction();
            $this->rendaService->criar((int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('rendas.index')
                ->with('sucesso', 'Renda cadastrada com sucesso.');
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
                ->with('erro', 'Erro ao cadastrar renda.')
                ->withInput();
        }
    }

    public function atualizar(AtualizarRendaRequest $request, Renda $renda): RedirectResponse
    {
        $this->authorize('update', $renda);

        try {
            DB::beginTransaction();
            $this->rendaService->atualizar($renda, (int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('rendas.index')
                ->with('sucesso', 'Renda atualizada com sucesso.');
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
                ->with('erro', 'Erro ao atualizar renda.');
        }
    }

    public function excluir(Renda $renda): RedirectResponse
    {
        $this->authorize('delete', $renda);

        try {
            DB::beginTransaction();
            $this->rendaService->excluir($renda, (int) Auth::id());
            DB::commit();

            return redirect()
                ->route('rendas.index')
                ->with('sucesso', 'Renda excluída com sucesso.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('rendas.index')
                ->with('erro', 'Erro ao excluir renda.');
        }
    }
}
