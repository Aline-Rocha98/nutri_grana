<?php

namespace App\Http\Controllers\ContaBancaria;

use App\Data\BancosSugeridos;
use App\Enum\TipoContaBancaria;
use App\Http\Controllers\Concerns\RethrowsAuthorizationExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContaBancaria\AtualizarContaBancariaRequest;
use App\Http\Requests\ContaBancaria\CriarContaBancariaRequest;
use App\Http\Resources\ContaBancaria\ContaBancariaResource;
use App\Models\ContaBancaria\ContaBancaria;
use App\Services\ContaBancaria\ContaBancariaService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContaBancariaController extends Controller
{
    use AuthorizesRequests;
    use RethrowsAuthorizationExceptions;

    public function __construct(
        private readonly ContaBancariaService $contaBancariaService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', ContaBancaria::class);

        $contasBancarias = $this->contaBancariaService->listarPorUsuario((int) Auth::id());

        return Inertia::render('ContaBancaria/Index', [
            'contasBancarias' => ContaBancariaResource::collection($contasBancarias)->resolve(),
            'tipos' => TipoContaBancaria::opcoesParaSelect(),
            'bancosSugeridos' => BancosSugeridos::todos(),
        ]);
    }

    public function criarContaBancaria(CriarContaBancariaRequest $request): RedirectResponse
    {
        $this->authorize('create', ContaBancaria::class);

        try {
            DB::beginTransaction();
            $this->contaBancariaService->criar((int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('contas-bancarias.index')
                ->with('sucesso', 'Conta bancária criada com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('contas-bancarias.index')
                ->with('erro', $e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->route('contas-bancarias.index')
                ->with('erro', 'Erro ao criar conta bancária.');
        }
    }

    public function atualizarContaBancaria(AtualizarContaBancariaRequest $request, ContaBancaria $contaBancaria): RedirectResponse
    {
        $this->authorize('update', $contaBancaria);

        try {
            DB::beginTransaction();
            $this->contaBancariaService->atualizar($contaBancaria, (int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('contas-bancarias.index')
                ->with('sucesso', 'Conta bancária atualizada com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('contas-bancarias.index')
                ->with('erro', $e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->route('contas-bancarias.index')
                ->with('erro', 'Erro ao atualizar conta bancária.');
        }
    }

    public function excluirContaBancaria(ContaBancaria $contaBancaria): RedirectResponse
    {
        $this->authorize('delete', $contaBancaria);

        try {
            DB::beginTransaction();
            $this->contaBancariaService->excluir($contaBancaria, (int) Auth::id());
            DB::commit();

            return redirect()
                ->route('contas-bancarias.index')
                ->with('sucesso', 'Conta bancária excluída com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('contas-bancarias.index')
                ->with('erro', $e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->route('contas-bancarias.index')
                ->with('erro', 'Erro ao excluir conta bancária.');
        }
    }
}
