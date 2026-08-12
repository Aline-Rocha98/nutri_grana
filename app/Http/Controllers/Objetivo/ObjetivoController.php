<?php

namespace App\Http\Controllers\Objetivo;

use App\Enum\TipoAporteObjetivo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Objetivo\AtualizarObjetivoRequest;
use App\Http\Requests\Objetivo\CriarAporteObjetivoRequest;
use App\Http\Requests\Objetivo\CriarObjetivoRequest;
use App\Http\Resources\ContaBancaria\ContaBancariaResource;
use App\Http\Resources\Objetivo\ObjetivoResource;
use App\Models\Objetivo\Objetivo;
use App\Services\ContaBancaria\ContaBancariaService;
use App\Services\Objetivo\AporteObjetivoService;
use App\Services\Objetivo\ObjetivoService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ObjetivoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ObjetivoService $objetivoService,
        private readonly AporteObjetivoService $aporteObjetivoService,
        private readonly ContaBancariaService $contaBancariaService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Objetivo::class);

        $idUsuario = (int) Auth::id();
        $objetivos = $this->objetivoService->listarDoUsuario($idUsuario);
        $contas = $this->contaBancariaService->listarPorUsuario($idUsuario)
            ->filter(fn ($conta) => $conta->arquivada?->value !== 'S');

        return Inertia::render('Objetivo/Index', [
            'objetivos' => ObjetivoResource::collection($objetivos)->resolve(),
            'contasBancarias' => ContaBancariaResource::collection($contas)->resolve(),
            'tiposAporte' => TipoAporteObjetivo::opcoesParaSelect(),
        ]);
    }

    public function criar(CriarObjetivoRequest $request): RedirectResponse
    {
        $this->authorize('create', Objetivo::class);

        try {
            DB::beginTransaction();
            $this->objetivoService->criar((int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('objetivos.index')
                ->with('sucesso', 'Objetivo criado com sucesso.');
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
                ->with('erro', 'Erro ao criar objetivo.')
                ->withInput();
        }
    }

    public function atualizar(AtualizarObjetivoRequest $request, Objetivo $objetivo): RedirectResponse
    {
        $this->authorize('update', $objetivo);

        try {
            DB::beginTransaction();
            $this->objetivoService->atualizar($objetivo, (int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('objetivos.index')
                ->with('sucesso', 'Objetivo atualizado com sucesso.');
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
                ->with('erro', 'Erro ao atualizar objetivo.');
        }
    }

    public function excluir(Objetivo $objetivo): RedirectResponse
    {
        $this->authorize('delete', $objetivo);

        try {
            DB::beginTransaction();
            $this->objetivoService->excluir($objetivo, (int) Auth::id());
            DB::commit();

            return redirect()
                ->route('objetivos.index')
                ->with('sucesso', 'Objetivo excluído com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('objetivos.index')
                ->with('erro', collect($e->errors())->flatten()->first());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('objetivos.index')
                ->with('erro', 'Erro ao excluir objetivo.');
        }
    }

    public function criarAporte(CriarAporteObjetivoRequest $request, Objetivo $objetivo): RedirectResponse
    {
        $this->authorize('update', $objetivo);

        try {
            DB::beginTransaction();
            $this->aporteObjetivoService->registrar(
                $objetivo,
                (int) Auth::id(),
                $request->validated()
            );
            DB::commit();

            return redirect()
                ->route('objetivos.index')
                ->with('sucesso', 'Aporte registrado com sucesso.');
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
                ->with('erro', 'Erro ao registrar aporte.')
                ->withInput();
        }
    }
}
