<?php

namespace App\Http\Controllers\FaturaCartao;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaturaCartao\BaixarFaturaCartaoRequest;
use App\Http\Resources\ContaBancaria\ContaBancariaResource;
use App\Http\Resources\FaturaCartao\FaturaCartaoResource;
use App\Http\Resources\Lancamento\LancamentoResource;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\FaturaCartao\FaturaCartao;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Services\ContaBancaria\ContaBancariaService;
use App\Services\FaturaCartao\FaturaCartaoService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FaturaCartaoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly FaturaCartaoService $faturaCartaoService,
        private readonly ContaBancariaService $contaBancariaService,
        private readonly ContaBancariaRepository $contaBancariaRepository,
    ) {}

    public function indexPorCartao(CartaoCredito $cartaoCredito): Response
    {
        $this->authorize('view', $cartaoCredito);

        $idUsuario = (int) Auth::id();
        $faturas = $this->faturaCartaoService->listarPorCartao($cartaoCredito, $idUsuario);
        $contas = $this->contaBancariaService->listarPorUsuario($idUsuario)
            ->filter(fn ($c) => $c->arquivada?->value !== 'S');

        return Inertia::render('FaturaCartao/Index', [
            'cartao' => [
                'id' => $cartaoCredito->id_cartao_credito,
                'nome' => $cartaoCredito->nome,
            ],
            'faturas' => FaturaCartaoResource::collection($faturas)->resolve(),
            'contasBancarias' => ContaBancariaResource::collection($contas)->resolve(),
            'urlVoltar' => route('cartoes-credito.index'),
        ]);
    }

    public function show(FaturaCartao $faturaCartao): Response
    {
        $this->authorize('view', $faturaCartao);

        $faturaCartao->load(['cartaoCredito', 'lancamentos.categoria', 'lancamentos.contaBancaria', 'lancamentos.cartaoCredito']);

        $valorTotal = (float) $faturaCartao->lancamentos
            ->filter(fn ($l) => $l->situacao?->value !== 'cancelado')
            ->sum('valor');
        $faturaCartao->setAttribute('valor_total', $valorTotal);

        $idUsuario = (int) Auth::id();
        $contas = $this->contaBancariaService->listarPorUsuario($idUsuario)
            ->filter(fn ($c) => $c->arquivada?->value !== 'S');

        return Inertia::render('FaturaCartao/Show', [
            'fatura' => (new FaturaCartaoResource($faturaCartao))->resolve(),
            'lancamentos' => LancamentoResource::collection($faturaCartao->lancamentos)->resolve(),
            'contasBancarias' => ContaBancariaResource::collection($contas)->resolve(),
            'urlVoltar' => route('faturas-cartao.index', $faturaCartao->cartaoCredito),
        ]);
    }

    public function baixar(BaixarFaturaCartaoRequest $request, FaturaCartao $faturaCartao): RedirectResponse
    {
        $this->authorize('update', $faturaCartao);

        $idUsuario = (int) Auth::id();
        $conta = $this->contaBancariaRepository->buscarParaUsuario(
            (int) $request->validated('id_conta_bancaria'),
            $idUsuario
        );

        if (! $conta) {
            return redirect()
                ->back()
                ->withErrors(['id_conta_bancaria' => 'Conta bancária inválida.']);
        }

        $dataPagamento = $request->validated('data_pagamento')
            ? Carbon::parse($request->validated('data_pagamento'))
            : Carbon::today();

        try {
            DB::beginTransaction();
            $this->faturaCartaoService->baixar($faturaCartao, $idUsuario, $conta, $dataPagamento);
            DB::commit();

            return redirect()
                ->route('faturas-cartao.show', $faturaCartao)
                ->with('sucesso', 'Fatura paga com sucesso. O valor foi debitado da conta.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($e->errors());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('erro', 'Erro ao pagar a fatura.');
        }
    }
}
