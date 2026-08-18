<?php

namespace App\Http\Controllers\Orcamento;

use App\Enum\TipoOrcamento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orcamento\AprovarOrcamentoServicoRequest;
use App\Http\Requests\Orcamento\AtualizarOrcamentoServicoRequest;
use App\Http\Requests\Orcamento\CriarOrcamentoServicoRequest;
use App\Models\Orcamento\OrcamentoServico;
use App\Services\Orcamento\OrcamentoServicoService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrcamentoServicoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly OrcamentoServicoService $orcamentoServicoService,
    ) {}

    public function criar(CriarOrcamentoServicoRequest $request): RedirectResponse
    {
        $this->authorize('create', OrcamentoServico::class);

        try {
            DB::beginTransaction();
            $this->orcamentoServicoService->criar((int) Auth::id(), $request->validated());
            DB::commit();

            return $this->redirecionarParaIndex()
                ->with('sucesso', 'Cotação registrada com sucesso.');
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
                ->with('erro', 'Erro ao registrar a cotação.')
                ->withInput();
        }
    }

    public function atualizar(
        AtualizarOrcamentoServicoRequest $request,
        OrcamentoServico $orcamentoServico,
    ): RedirectResponse {
        $this->authorize('update', $orcamentoServico);

        try {
            DB::beginTransaction();
            $this->orcamentoServicoService->atualizar(
                $orcamentoServico,
                (int) Auth::id(),
                $request->validated(),
            );
            DB::commit();

            return $this->redirecionarParaIndex()
                ->with('sucesso', 'Cotação atualizada com sucesso.');
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
                ->with('erro', 'Erro ao atualizar a cotação.');
        }
    }

    public function aprovar(
        AprovarOrcamentoServicoRequest $request,
        OrcamentoServico $orcamentoServico,
    ): RedirectResponse {
        $this->authorize('approve', $orcamentoServico);

        try {
            DB::beginTransaction();
            $this->orcamentoServicoService->aprovar(
                $orcamentoServico,
                (int) Auth::id(),
                $request->validated(),
            );
            DB::commit();

            return $this->redirecionarParaIndex()
                ->with('sucesso', 'Cotação aprovada. Os compromissos futuros foram gerados como previstos.');
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
                ->with('erro', 'Erro ao aprovar a cotação.');
        }
    }

    public function recusar(Request $request, OrcamentoServico $orcamentoServico): RedirectResponse
    {
        $this->authorize('reject', $orcamentoServico);

        try {
            DB::beginTransaction();
            $this->orcamentoServicoService->recusar($orcamentoServico, (int) Auth::id());
            DB::commit();

            return $this->redirecionarParaIndex()
                ->with('sucesso', 'Cotação recusada.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($e->errors());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('erro', 'Erro ao recusar a cotação.');
        }
    }

    public function excluir(Request $request, OrcamentoServico $orcamentoServico): RedirectResponse
    {
        $this->authorize('delete', $orcamentoServico);

        try {
            DB::beginTransaction();
            $this->orcamentoServicoService->excluir($orcamentoServico, (int) Auth::id());
            DB::commit();

            return $this->redirecionarParaIndex()
                ->with('sucesso', 'Cotação excluída com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarParaIndex()
                ->with('erro', collect($e->errors())->flatten()->first());
        } catch (Exception $e) {
            DB::rollBack();

            return $this->redirecionarParaIndex()
                ->with('erro', 'Erro ao excluir a cotação.');
        }
    }

    private function redirecionarParaIndex(): RedirectResponse
    {
        $hoje = now();

        return redirect()->route('orcamentos.index', [
            'ano' => (int) $hoje->year,
            'mes' => (int) $hoje->month,
            'tipo' => TipoOrcamento::PorServico->value,
        ]);
    }
}
