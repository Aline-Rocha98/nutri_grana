<?php

namespace App\Http\Controllers\Orcamento;

use App\Enum\TipoOrcamento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orcamento\AtualizarOrcamentoServicoRequest;
use App\Http\Requests\Orcamento\CriarOrcamentoServicoRequest;
use App\Http\Requests\Orcamento\SimularOrcamentoServicoRequest;
use App\Http\Resources\Orcamento\OrcamentoServicoResource;
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
                ->with('sucesso', 'Orçamento por serviço criado com sucesso.');
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
                ->with('erro', 'Erro ao criar orçamento por serviço.')
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
                ->with('sucesso', 'Orçamento por serviço atualizado com sucesso.');
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
                ->with('erro', 'Erro ao atualizar orçamento por serviço.');
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
                ->with('sucesso', 'Orçamento por serviço excluído com sucesso.');
        } catch (Exception $e) {
            DB::rollBack();

            return $this->redirecionarParaIndex()
                ->with('erro', 'Erro ao excluir orçamento por serviço.');
        }
    }

    public function simular(SimularOrcamentoServicoRequest $request): RedirectResponse
    {
        $this->authorize('create', OrcamentoServico::class);

        try {
            $simulacao = $this->orcamentoServicoService->simular(
                (int) Auth::id(),
                $request->validated(),
            );

            return $this->redirecionarParaIndex()
                ->with('simulacao_orcamento_servico', [
                    'descricao' => $request->input('descricao'),
                    'valor' => number_format((float) $request->input('valor'), 2, ',', '.'),
                    'valor_numero' => (float) $request->input('valor'),
                    'data_orcamento' => $request->input('data_orcamento'),
                    'data_validade' => $request->input('data_validade'),
                    'observacao' => $request->input('observacao'),
                    'viabilidade' => [
                        'mensagem_principal' => $simulacao['mensagem_principal'],
                        'mensagem_disponivel' => $simulacao['mensagem_disponivel'],
                        'mensagem_alerta' => $simulacao['mensagem_alerta'],
                        'pago_integralmente_agora' => $simulacao['pago_integralmente_agora'],
                        'meses_ate_pagar' => $simulacao['meses_ate_pagar'],
                        'compromete_fluxo' => $simulacao['compromete_fluxo'],
                        'saldo_atual_contas' => $simulacao['saldo_atual_contas_formatado'],
                        'saldo_disponivel_planejamento' => $simulacao['saldo_disponivel_planejamento_formatado'],
                        'comparativo' => [
                            'mes_rotulo' => $simulacao['comparativo']['mes_rotulo'],
                            'saldo_sem_orcamento' => $simulacao['comparativo']['saldo_sem_orcamento_formatado'],
                            'saldo_sem_orcamento_numero' => $simulacao['comparativo']['saldo_sem_orcamento'],
                            'saldo_com_orcamento' => $simulacao['comparativo']['saldo_com_orcamento_formatado'],
                            'saldo_com_orcamento_numero' => $simulacao['comparativo']['saldo_com_orcamento'],
                        ],
                    ],
                ]);
        } catch (ValidationException $e) {
            return $this->redirecionarParaIndex()
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            return $this->redirecionarParaIndex()
                ->with('erro', 'Erro ao simular o orçamento.')
                ->withInput();
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
