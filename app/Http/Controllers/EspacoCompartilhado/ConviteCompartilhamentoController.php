<?php

namespace App\Http\Controllers\EspacoCompartilhado;

use App\Exceptions\EspacoCompartilhado\RespostaConviteInvalidaException;
use App\Http\Controllers\Concerns\RedirecionaErrosDeValidacao;
use App\Http\Controllers\Concerns\RethrowsAuthorizationExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\EspacoCompartilhado\AceitarConviteComCodigoRequest;
use App\Http\Requests\EspacoCompartilhado\CriarConviteEmailRequest;
use App\Models\EspacoCompartilhado\ConviteCompartilhamento;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Services\EspacoCompartilhado\ConviteCompartilhamentoService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConviteCompartilhamentoController extends Controller
{
    use AuthorizesRequests;
    use RedirecionaErrosDeValidacao;
    use RethrowsAuthorizationExceptions;

    public function __construct(
        private readonly ConviteCompartilhamentoService $conviteService,
    ) {}

    public function enviarConviteParaUsuario(CriarConviteEmailRequest $request, EspacoCompartilhado $espacoCompartilhado): RedirectResponse
    {
        $this->authorize('convidar', $espacoCompartilhado);

        try {
            DB::beginTransaction();
            $resultado = $this->conviteService->convidarUsuarioCadastradoPorEmail(
                $espacoCompartilhado,
                $request->user(),
                $request->validated('email')
            );
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Convite enviado. Passe o código para a pessoa convidada.')
                ->with('codigoConvite', [
                    'codigo' => $resultado['codigo'],
                    'nome_convidado' => $resultado['convidado']->nome,
                    'email_convidado' => $resultado['convite']->email_destinatario,
                    'expira_em' => $resultado['convite']->expira_em?->toIso8601String(),
                ]);
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComErrosDeValidacao($e);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao enviar convite.')
                ->withInput();
        }
    }

    public function revogarConvite(EspacoCompartilhado $espacoCompartilhado, ConviteCompartilhamento $conviteCompartilhamento): RedirectResponse
    {
        $this->authorize('convidar', $espacoCompartilhado);
        $this->authorize('revogar', $conviteCompartilhamento);

        try {
            DB::beginTransaction();
            $this->conviteService->revogarConvite($espacoCompartilhado, $conviteCompartilhamento, (int) Auth::id());
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Convite revogado.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComPrimeiroErro($e);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao revogar convite.');
        }
    }

    public function aceitarConviteComCodigo(
        AceitarConviteComCodigoRequest $request,
        ConviteCompartilhamento $conviteCompartilhamento,
    ): RedirectResponse {
        $this->authorize('responder', $conviteCompartilhamento);

        try {
            DB::beginTransaction();
            $espaco = $this->conviteService->aceitarConviteComCodigo(
                $conviteCompartilhamento,
                $request->user(),
                $request->validated('email_convidante'),
                $request->validated('codigo'),
            );
            DB::commit();

            return redirect()
                ->route('compartilhados.show', ['espacoCompartilhado' => $espaco])
                ->with('sucesso', "Você agora participa do espaço \"{$espaco->nome}\".");
        } catch (RespostaConviteInvalidaException $e) {
            DB::commit();

            return redirect()
                ->back()
                ->withErrors($e->obterErrosPorCampo())
                ->withInput($request->only('email_convidante'));
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComErrosDeValidacao($e, ['convite']);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao aceitar convite.');
        }
    }

    public function recusarConvite(Request $request, ConviteCompartilhamento $conviteCompartilhamento): RedirectResponse
    {
        $this->authorize('responder', $conviteCompartilhamento);

        try {
            DB::beginTransaction();
            $this->conviteService->recusarConvite($conviteCompartilhamento, $request->user());
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Convite recusado.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->redirecionarComPrimeiroErro($e);
        } catch (Exception $e) {
            DB::rollBack();
            $this->rethrowIfAuthorization($e);

            return redirect()
                ->back()
                ->with('erro', 'Erro ao recusar convite.');
        }
    }
}
