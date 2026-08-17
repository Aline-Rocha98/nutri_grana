<?php

namespace App\Http\Controllers\Lancamento;

use App\Enum\FormaPagamento;
use App\Enum\FrequenciaRecorrencia;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lancamento\AtualizarLancamentoRequest;
use App\Http\Requests\Lancamento\ConfirmarReceitaRequest;
use App\Http\Requests\Lancamento\CriarLancamentoRequest;
use App\Http\Resources\CartaoCredito\CartaoCreditoResource;
use App\Http\Resources\Categoria\CategoriaResource;
use App\Http\Resources\ContaBancaria\ContaBancariaResource;
use App\Http\Resources\Lancamento\LancamentoResource;
use App\Models\Lancamento\Lancamento;
use App\Services\CartaoCredito\CartaoCreditoService;
use App\Services\Categoria\CategoriaService;
use App\Services\ContaBancaria\ContaBancariaService;
use App\Services\Lancamento\LancamentoService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LancamentoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly LancamentoService $lancamentoService,
        private readonly ContaBancariaService $contaBancariaService,
        private readonly CartaoCreditoService $cartaoCreditoService,
        private readonly CategoriaService $categoriaService,
    ) {}

    public function index(Request $request, ?int $ano = null, ?int $mes = null): Response
    {
        $this->authorize('viewAny', Lancamento::class);

        $hoje = now();
        $ano = $ano ?? (int) $hoje->year;
        $mes = $mes ?? (int) $hoje->month;

        if ($mes < 1 || $mes > 12 || $ano < 2000 || $ano > 2100) {
            abort(404);
        }

        $idUsuario = (int) Auth::id();
        $filtros = $request->only(['tipo', 'situacao', 'id_categoria']);

        $paginator = $this->lancamentoService->listarDoMes($idUsuario, $ano, $mes, $filtros, 20);
        $totais = $this->lancamentoService->totaisDoMes($idUsuario, $ano, $mes);

        $contas = $this->contaBancariaService->listarPorUsuario($idUsuario)
            ->filter(fn ($c) => $c->arquivada?->value !== 'S');
        $cartoes = $this->cartaoCreditoService->listarPorUsuario($idUsuario)
            ->filter(fn ($c) => $c->arquivada?->value !== 'S');
        $categorias = $this->categoriaService->listarPorUsuario($idUsuario);

        return Inertia::render('Lancamento/Index', [
            'ano' => $ano,
            'mes' => $mes,
            'lancamentos' => [
                'data' => LancamentoResource::collection($paginator->getCollection())->resolve(),
                'links' => $paginator->linkCollection()->values()->all(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
            'totais' => $totais,
            'filtros' => [
                'tipo' => $filtros['tipo'] ?? null,
                'situacao' => $filtros['situacao'] ?? null,
                'id_categoria' => $filtros['id_categoria'] ?? null,
            ],
            'contasBancarias' => ContaBancariaResource::collection($contas)->resolve(),
            'cartoesCredito' => CartaoCreditoResource::collection($cartoes)->resolve(),
            'categorias' => CategoriaResource::collection($categorias)->resolve(),
            'tipos' => TipoLancamento::opcoesParaSelect(),
            'formasPagamento' => FormaPagamento::opcoesParaSelect(),
            'situacoes' => SituacaoLancamento::opcoesParaSelect(),
            'frequencias' => FrequenciaRecorrencia::opcoesParaSelect(),
            'urlCriar' => route('lancamentos.criar'),
            'urlBase' => url('/lancamentos'),
        ]);
    }

    public function criar(CriarLancamentoRequest $request): RedirectResponse
    {
        $this->authorize('create', Lancamento::class);

        $data = $request->validated();
        $dataVencimento = $data['data_vencimento'];
        $ano = (int) date('Y', strtotime($dataVencimento));
        $mes = (int) date('n', strtotime($dataVencimento));

        try {
            DB::beginTransaction();
            $this->lancamentoService->criar((int) Auth::id(), $data);
            DB::commit();

            return redirect()
                ->route('lancamentos.index', ['ano' => $ano, 'mes' => $mes])
                ->with('sucesso', 'Lançamento criado com sucesso.');
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
                ->with('erro', 'Erro ao criar lançamento.')
                ->withInput();
        }
    }

    public function atualizar(AtualizarLancamentoRequest $request, Lancamento $lancamento): RedirectResponse
    {
        $this->authorize('update', $lancamento);

        try {
            DB::beginTransaction();
            $atualizado = $this->lancamentoService->atualizar(
                $lancamento,
                (int) Auth::id(),
                $request->validated()
            );
            DB::commit();

            $ano = (int) $atualizado->data_vencimento->year;
            $mes = (int) $atualizado->data_vencimento->month;

            return redirect()
                ->route('lancamentos.index', ['ano' => $ano, 'mes' => $mes])
                ->with('sucesso', 'Lançamento atualizado com sucesso.');
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
                ->with('erro', 'Erro ao atualizar lançamento.');
        }
    }

    public function alterarSituacao(Request $request, Lancamento $lancamento): RedirectResponse
    {
        $this->authorize('update', $lancamento);

        $request->validate([
            'situacao' => ['required', 'in:pendente,pago,cancelado'],
        ]);

        try {
            DB::beginTransaction();
            $this->lancamentoService->alterarSituacao(
                $lancamento,
                (int) Auth::id(),
                SituacaoLancamento::from($request->input('situacao'))
            );
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Situação atualizada.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->with('erro', collect($e->errors())->flatten()->first());
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('erro', 'Erro ao atualizar situação.');
        }
    }

    public function confirmarReceita(ConfirmarReceitaRequest $request, Lancamento $lancamento): RedirectResponse
    {
        $this->authorize('update', $lancamento);

        try {
            DB::beginTransaction();
            $this->lancamentoService->confirmarReceita(
                $lancamento,
                (int) Auth::id(),
                $request->validated()
            );
            DB::commit();

            return redirect()
                ->back()
                ->with('sucesso', 'Receita confirmada com sucesso.');
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
                ->with('erro', 'Erro ao confirmar receita.');
        }
    }

    public function excluir(Request $request, Lancamento $lancamento): RedirectResponse
    {
        $this->authorize('delete', $lancamento);

        $futuras = filter_var($request->input('futuras'), FILTER_VALIDATE_BOOLEAN);
        $ano = (int) $lancamento->data_vencimento->year;
        $mes = (int) $lancamento->data_vencimento->month;

        try {
            DB::beginTransaction();
            $this->lancamentoService->excluir($lancamento, (int) Auth::id(), $futuras);
            DB::commit();

            return redirect()
                ->route('lancamentos.index', ['ano' => $ano, 'mes' => $mes])
                ->with('sucesso', 'Lançamento excluído.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('erro', 'Erro ao excluir lançamento.');
        }
    }
}
