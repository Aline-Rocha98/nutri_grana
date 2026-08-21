<?php

namespace App\Http\Controllers\Dashboard;

use App\Enum\PeriodoDashboard;
use App\Enum\WidgetDashboard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DashboardDadosRequest;
use App\Http\Resources\Dashboard\DashboardResource;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(): Response
    {
        $usuario = Auth::user();

        return Inertia::render('Dashboard/Index', [
            'usuario' => [
                'nome' => $usuario->nome,
            ],
            'dataHoje' => now()->format('d/m/Y'),
            'widgetsPadrao' => WidgetDashboard::valores(),
            'periodoPadrao' => PeriodoDashboard::Atual->value,
            'urlDados' => route('dashboard.dados'),
        ]);
    }

    public function dados(DashboardDadosRequest $request): JsonResponse
    {
        $validado = $request->validated();
        $periodo = PeriodoDashboard::tryFrom($validado['periodo'] ?? PeriodoDashboard::Atual->value)
            ?? PeriodoDashboard::Atual;

        $dados = $this->dashboardService->obterDados(
            (int) Auth::id(),
            $validado['widgets'],
            $periodo,
        );

        return (new DashboardResource($dados))
            ->response()
            ->setStatusCode(200);
    }
}
