<?php

namespace App\Http\Controllers\PlanoFinanceiro;

use App\Enum\CorItemPlanoFinanceiro;
use App\Enum\TipoCalculoItemPlano;
use App\Http\Controllers\Concerns\RethrowsAuthorizationExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlanoFinanceiro\AtualizarPlanoFinanceiroRequest;
use App\Http\Requests\PlanoFinanceiro\CriarPlanoFinanceiroRequest;
use App\Http\Resources\PlanoFinanceiro\PlanoFinanceiroResource;
use App\Models\PlanoFinanceiro\PlanoFinanceiro;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Repositories\Objetivo\ObjetivoRepository;
use App\Services\Alocacao\AlocacaoService;
use App\Services\PlanoFinanceiro\PlanoFinanceiroService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlanoFinanceiroController extends Controller
{
    use AuthorizesRequests;
    use RethrowsAuthorizationExceptions;

    public function __construct(
        private readonly PlanoFinanceiroService $planoFinanceiroService,
        private readonly ContaBancariaRepository $contaBancariaRepository,
        private readonly ObjetivoRepository $objetivoRepository,
        private readonly AlocacaoService $alocacaoService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', PlanoFinanceiro::class);

        $idUsuario = (int) Auth::id();
        $contasBancarias = $this->contaBancariaRepository->listarOpcoesAtivasDoUsuario($idUsuario)
            ->map(fn ($conta) => [
                'id' => $conta->id_conta_bancaria,
                'nome' => $conta->nome,
            ])
            ->values();

        $objetivos = $this->objetivoRepository->listarOpcoesDoUsuario($idUsuario)
            ->map(fn ($objetivo) => [
                'id' => $objetivo->id_objetivo,
                'descricao' => $objetivo->descricao,
            ])
            ->values();

        $alocacoes = $this->alocacaoService->listarOpcoesDeAlocacoesDoUsuario($idUsuario)
            ->map(fn ($alocacao) => [
                'id' => $alocacao->id_alocacao,
                'nome' => $alocacao->nome,
            ])
            ->values();

        return Inertia::render('PlanoFinanceiro/Index', [
            'planosFinanceiros' => PlanoFinanceiroResource::collection(
                $this->planoFinanceiroService->listarPlanosDoUsuario($idUsuario)
            )->resolve(),
            'contasBancarias' => $contasBancarias,
            'objetivos' => $objetivos,
            'alocacoes' => $alocacoes,
            'urlAlocacoes' => route('alocacoes.index'),
            'tiposCalculo' => TipoCalculoItemPlano::opcoesParaSelect(),
            'coresItem' => CorItemPlanoFinanceiro::opcoesParaSelect(),
            'urlCriar' => route('planos-financeiros.criar'),
        ]);
    }

    public function criarPlanoFinanceiro(CriarPlanoFinanceiroRequest $request): RedirectResponse
    {
        $this->authorize('create', PlanoFinanceiro::class);

        try {
            DB::beginTransaction();
            $this->planoFinanceiroService->criarPlanoFinanceiro((int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('planos-financeiros.index')
                ->with('sucesso', 'Plano financeiro criado com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao criar plano financeiro.')
                ->withInput();
        }
    }

    public function atualizarPlanoFinanceiro(AtualizarPlanoFinanceiroRequest $request, PlanoFinanceiro $planoFinanceiro): RedirectResponse
    {
        $this->authorize('update', $planoFinanceiro);

        try {
            DB::beginTransaction();
            $this->planoFinanceiroService->atualizarPlanoFinanceiro($planoFinanceiro, (int) Auth::id(), $request->validated());
            DB::commit();

            return redirect()
                ->route('planos-financeiros.index')
                ->with('sucesso', 'Plano financeiro atualizado com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao atualizar plano financeiro.');
        }
    }

    public function alternarSituacaoPlanoFinanceiro(PlanoFinanceiro $planoFinanceiro): RedirectResponse
    {
        $this->authorize('update', $planoFinanceiro);

        try {
            DB::beginTransaction();
            $planoAtualizado = $this->planoFinanceiroService->alternarSituacaoPlanoFinanceiro($planoFinanceiro, (int) Auth::id());
            DB::commit();

            $mensagem = $planoAtualizado->estaAtivo()
                ? 'Plano financeiro ativado com sucesso.'
                : 'Plano financeiro inativado com sucesso.';

            return redirect()
                ->route('planos-financeiros.index')
                ->with('sucesso', $mensagem);
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('planos-financeiros.index')
                ->with('erro', collect($e->errors())->flatten()->first());
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->route('planos-financeiros.index')
                ->with('erro', 'Erro ao alterar a situação do plano financeiro.');
        }
    }

    public function definirPlanoFinanceiroPadrao(PlanoFinanceiro $planoFinanceiro): RedirectResponse
    {
        $this->authorize('update', $planoFinanceiro);

        try {
            DB::beginTransaction();
            $this->planoFinanceiroService->definirPlanoFinanceiroPadrao($planoFinanceiro, (int) Auth::id());
            DB::commit();

            return redirect()
                ->route('planos-financeiros.index')
                ->with('sucesso', 'Plano definido como padrão.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('planos-financeiros.index')
                ->with('erro', collect($e->errors())->flatten()->first());
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->route('planos-financeiros.index')
                ->with('erro', 'Erro ao definir o plano padrão.');
        }
    }

    public function excluirPlanoFinanceiro(PlanoFinanceiro $planoFinanceiro): RedirectResponse
    {
        $this->authorize('delete', $planoFinanceiro);

        try {
            DB::beginTransaction();
            $this->planoFinanceiroService->excluirPlanoFinanceiro($planoFinanceiro, (int) Auth::id());
            DB::commit();

            return redirect()
                ->route('planos-financeiros.index')
                ->with('sucesso', 'Plano financeiro excluído com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('planos-financeiros.index')
                ->with('erro', collect($e->errors())->flatten()->first());
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->route('planos-financeiros.index')
                ->with('erro', 'Erro ao excluir plano financeiro.');
        }
    }
}
