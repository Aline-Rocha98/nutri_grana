<?php

namespace App\Http\Controllers\ContasBancarias;

use App\Enum\TipoContaBancaria;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContasBancarias\AtualizarContaBancariaRequest;
use App\Http\Requests\ContasBancarias\CriarContaBancariaRequest;
use App\Models\ContasBancarias\ContaBancaria;
use App\Services\ContasBancarias\ContaBancariaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContaBancariaController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ContaBancariaService $servico,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', ContaBancaria::class);

        $contasBancarias = $this->servico->listarPorUsuario((int) Auth::id());
        $tipos = TipoContaBancaria::opcoesParaSelect();

        return view('contas-bancarias.index', compact('contasBancarias', 'tipos'));
    }

    public function criar(CriarContaBancariaRequest $request): RedirectResponse
    {
        $this->servico->criar((int) Auth::id(), $request->validated());

        return redirect()
            ->route('contas-bancarias.index')
            ->with('sucesso', 'Conta bancária criada com sucesso.');
    }

    public function atualizar(AtualizarContaBancariaRequest $request, ContaBancaria $contaBancaria): RedirectResponse
    {
        $this->servico->atualizar($contaBancaria, (int) Auth::id(), $request->validated());

        return redirect()
            ->route('contas-bancarias.index')
            ->with('sucesso', 'Conta bancária atualizada com sucesso.');
    }

    public function excluir(ContaBancaria $contaBancaria): RedirectResponse
    {
        $this->authorize('delete', $contaBancaria);

        $this->servico->excluir($contaBancaria, (int) Auth::id());

        return redirect()
            ->route('contas-bancarias.index')
            ->with('sucesso', 'Conta bancária excluída com sucesso.');
    }
}
