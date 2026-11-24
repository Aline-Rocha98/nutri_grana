<?php

namespace App\Http\Controllers\EspacoCompartilhado;

use App\Http\Controllers\Concerns\RedirecionaErrosDeValidacao;
use App\Http\Controllers\Concerns\RethrowsAuthorizationExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\EspacoCompartilhado\AssumirLancamentoCompartilhadoRequest;
use App\Http\Requests\EspacoCompartilhado\AtualizarLancamentoCompartilhadoRequest;
use App\Http\Requests\EspacoCompartilhado\ConfirmarVersaoLancamentoCompartilhadoRequest;
use App\Http\Requests\EspacoCompartilhado\CriarLancamentoCompartilhadoRequest;
use App\Http\Requests\EspacoCompartilhado\DarBaixaLancamentoCompartilhadoRequest;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Services\EspacoCompartilhado\BaixaLancamentoCompartilhadoService;
use App\Services\EspacoCompartilhado\LancamentoCompartilhadoService;
use App\Services\EspacoCompartilhado\TransferenciaResponsavelService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class LancamentoCompartilhadoController extends Controller
{
    use AuthorizesRequests;
    use RedirecionaErrosDeValidacao;
    use RethrowsAuthorizationExceptions;

    private const CAMPOS_DE_ERRO_SEM_FORMULARIO = ['versao', 'lancamento'];

    public function __construct(
        private readonly LancamentoCompartilhadoService $lancamentoCompartilhadoService,
        private readonly BaixaLancamentoCompartilhadoService $baixaLancamentoService,
        private readonly TransferenciaResponsavelService $transferenciaResponsavelService,
    ) {}

    public function criarLancamentoCompartilhado(CriarLancamentoCompartilhadoRequest $request, EspacoCompartilhado $espacoCompartilhado): RedirectResponse
    {
        $this->authorize('create', [LancamentoCompartilhado::class, $espacoCompartilhado]);

        try {
            DB::beginTransaction();
            $lancamento = $this->lancamentoCompartilhadoService->criarLancamentoCompartilhado(
                $espacoCompartilhado,
                (int) Auth::id(),
                $request->validated()
            );
            DB::commit();

            return redirect()
                ->route('compartilhados.show', $this->montarParametrosDoMesDoLancamento($espacoCompartilhado, $lancamento))
                ->with('sucesso', 'Lançamento compartilhado criado com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComErrosDeValidacao($e, self::CAMPOS_DE_ERRO_SEM_FORMULARIO);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao criar lançamento compartilhado.')
                ->withInput();
        }
    }

    public function atualizarLancamentoCompartilhado(
        AtualizarLancamentoCompartilhadoRequest $request,
        EspacoCompartilhado $espacoCompartilhado,
        LancamentoCompartilhado $lancamentoCompartilhado,
    ): RedirectResponse {
        $this->authorize('view', $espacoCompartilhado);
        $this->authorize('update', $lancamentoCompartilhado);

        try {
            DB::beginTransaction();
            $this->lancamentoCompartilhadoService->atualizarLancamentoCompartilhado(
                $espacoCompartilhado,
                $lancamentoCompartilhado,
                (int) Auth::id(),
                $request->validated()
            );
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Lançamento compartilhado atualizado com sucesso.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComErrosDeValidacao($e, self::CAMPOS_DE_ERRO_SEM_FORMULARIO);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao atualizar lançamento compartilhado.')
                ->withInput();
        }
    }

    public function excluirLancamentoCompartilhado(
        Request $request,
        EspacoCompartilhado $espacoCompartilhado,
        LancamentoCompartilhado $lancamentoCompartilhado,
    ): RedirectResponse {
        $this->authorize('view', $espacoCompartilhado);
        $this->authorize('delete', $lancamentoCompartilhado);

        try {
            DB::beginTransaction();
            $this->lancamentoCompartilhadoService->excluirLancamentoCompartilhado(
                $lancamentoCompartilhado,
                (int) Auth::id(),
                $request->boolean('encerrar_recorrencia')
            );
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Lançamento compartilhado excluído.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComPrimeiroErro($e);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao excluir lançamento compartilhado.');
        }
    }

    public function darBaixaLancamentoCompartilhado(
        DarBaixaLancamentoCompartilhadoRequest $request,
        EspacoCompartilhado $espacoCompartilhado,
        LancamentoCompartilhado $lancamentoCompartilhado,
    ): RedirectResponse {
        $this->authorize('view', $espacoCompartilhado);

        $respostaAutorizacao = Gate::inspect('darBaixa', $lancamentoCompartilhado);

        if ($respostaAutorizacao->denied()) {
            return redirect()
                ->back()
                ->with('erro', $respostaAutorizacao->message());
        }

        try {
            DB::beginTransaction();
            $this->baixaLancamentoService->darBaixaNoLancamentoCompartilhado(
                $espacoCompartilhado,
                $lancamentoCompartilhado,
                $request->user(),
                $request->validated()
            );
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Lançamento marcado como pago. O valor entra no seu planejamento unificado.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComErrosDeValidacao($e, self::CAMPOS_DE_ERRO_SEM_FORMULARIO);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao dar baixa no lançamento compartilhado.')
                ->withInput();
        }
    }

    public function estornarBaixaLancamentoCompartilhado(
        ConfirmarVersaoLancamentoCompartilhadoRequest $request,
        EspacoCompartilhado $espacoCompartilhado,
        LancamentoCompartilhado $lancamentoCompartilhado,
    ): RedirectResponse {
        $this->authorize('view', $espacoCompartilhado);

        $respostaAutorizacao = Gate::inspect('estornarBaixa', $lancamentoCompartilhado);

        if ($respostaAutorizacao->denied()) {
            return redirect()
                ->back()
                ->with('erro', $respostaAutorizacao->message() ?? 'Somente o responsável pode estornar a baixa.');
        }

        try {
            DB::beginTransaction();
            $this->baixaLancamentoService->estornarBaixaDoLancamento(
                $espacoCompartilhado,
                $lancamentoCompartilhado,
                $request->user(),
                (int) $request->validated('versao')
            );
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Baixa estornada. O lançamento voltou a ficar pendente no espaço.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComPrimeiroErro($e);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao estornar a baixa.');
        }
    }

    public function assumirLancamentoCompartilhado(
        AssumirLancamentoCompartilhadoRequest $request,
        EspacoCompartilhado $espacoCompartilhado,
        LancamentoCompartilhado $lancamentoCompartilhado,
    ): RedirectResponse {
        $this->authorize('view', $espacoCompartilhado);
        $this->authorize('assumirResponsabilidade', $lancamentoCompartilhado);

        try {
            DB::beginTransaction();
            $this->transferenciaResponsavelService->assumirLancamento(
                $espacoCompartilhado,
                $lancamentoCompartilhado,
                $request->user(),
                (int) $request->validated('versao'),
                $request->validated('id_categoria')
            );
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Agora você é responsável por este lançamento.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComPrimeiroErro($e);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao assumir o lançamento.');
        }
    }

    private function montarParametrosDoMesDoLancamento(EspacoCompartilhado $espaco, LancamentoCompartilhado $lancamento): array
    {
        $dataVencimento = Carbon::parse($lancamento->data_vencimento);

        return [
            'espacoCompartilhado' => $espaco,
            'ano' => (int) $dataVencimento->year,
            'mes' => (int) $dataVencimento->month,
        ];
    }
}
