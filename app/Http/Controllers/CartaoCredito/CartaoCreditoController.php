<?php

namespace App\Http\Controllers\CartaoCredito;

use App\Data\BancosSugeridos;
use App\Enum\BandeiraCartaoCredito;
use App\Enum\SimNao;
use App\Http\Controllers\Controller;
use App\Http\Requests\CartaoCredito\ArquivarCartaoCreditoRequest;
use App\Http\Requests\CartaoCredito\AtualizarCartaoCreditoRequest;
use App\Http\Requests\CartaoCredito\CriarCartaoCreditoRequest;
use App\Http\Resources\CartaoCredito\CartaoCreditoResource;
use App\Models\CartaoCredito\CartaoCredito;
use App\Services\CartaoCredito\CartaoCreditoService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartaoCreditoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CartaoCreditoService $cartaoCreditoService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', CartaoCredito::class);

        $cartoesCredito = $this->cartaoCreditoService->listarPorUsuario((int) Auth::id());

        return Inertia::render('CartaoCredito/Index', [
            'cartoesCredito' => CartaoCreditoResource::collection($cartoesCredito)->resolve(),
            'bandeiras' => BandeiraCartaoCredito::opcoesParaSelect(),
            'bancosSugeridos' => BancosSugeridos::todos(),
        ]);
    }

    public function criarCartaoCredito(CriarCartaoCreditoRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $this->cartaoCreditoService->criar((int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('cartoes-credito.index')
                ->with('sucesso', 'Cartão de crédito criado com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('cartoes-credito.index')
                ->with('erro', $e->errors());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('cartoes-credito.index')
                ->with('erro', 'Erro ao criar cartão de crédito.');
        }
    }

    public function atualizarCartaoCredito(AtualizarCartaoCreditoRequest $request, CartaoCredito $cartaoCredito): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $this->cartaoCreditoService->atualizar($cartaoCredito, (int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('cartoes-credito.index')
                ->with('sucesso', 'Cartão de crédito atualizado com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('cartoes-credito.index')
                ->with('erro', $e->errors());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('cartoes-credito.index')
                ->with('erro', 'Erro ao atualizar cartão de crédito.');
        }
    }

    public function arquivarCartaoCredito(ArquivarCartaoCreditoRequest $request, CartaoCredito $cartaoCredito): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $arquivada = SimNao::from($request->validated('arquivada'));
            $this->cartaoCreditoService->arquivar(
                $cartaoCredito,
                (int) Auth::id(),
                $arquivada
            );
            DB::commit();

            $mensagem = $arquivada === SimNao::Sim
                ? 'Cartão de crédito arquivado com sucesso.'
                : 'Cartão de crédito desarquivado com sucesso.';

            return redirect()
                ->route('cartoes-credito.index')
                ->with('sucesso', $mensagem);
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('cartoes-credito.index')
                ->with('erro', 'Erro ao arquivar cartão de crédito.');
        }
    }

    public function excluirCartaoCredito(CartaoCredito $cartaoCredito): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $this->cartaoCreditoService->excluir($cartaoCredito, (int) Auth::id());
            DB::commit();

            return redirect()
                ->route('cartoes-credito.index')
                ->with('sucesso', 'Cartão de crédito excluído com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('cartoes-credito.index')
                ->with('erro', $e->errors());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('cartoes-credito.index')
                ->with('erro', 'Erro ao excluir cartão de crédito.');
        }
    }
}
