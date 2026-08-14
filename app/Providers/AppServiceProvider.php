<?php

namespace App\Providers;

use App\Models\CartaoCredito\CartaoCredito;
use App\Models\Categoria\Categoria;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\FaturaCartao\FaturaCartao;
use App\Models\Lancamento\Lancamento;
use App\Models\Objetivo\Objetivo;
use App\Models\Orcamento\Orcamento;
use App\Models\Orcamento\OrcamentoServico;
use App\Models\Usuario\Usuario;
use App\Policies\CartaoCredito\CartaoCreditoPolicy;
use App\Policies\Categoria\CategoriaPolicy;
use App\Policies\ContaBancaria\ContaBancariaPolicy;
use App\Policies\FaturaCartao\FaturaCartaoPolicy;
use App\Policies\Lancamento\LancamentoPolicy;
use App\Policies\Objetivo\ObjetivoPolicy;
use App\Policies\Orcamento\OrcamentoPolicy;
use App\Policies\Orcamento\OrcamentoServicoPolicy;
use App\Policies\Usuario\UsuarioPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ContaBancaria::class, ContaBancariaPolicy::class);
        Gate::policy(CartaoCredito::class, CartaoCreditoPolicy::class);
        Gate::policy(Categoria::class, CategoriaPolicy::class);
        Gate::policy(Usuario::class, UsuarioPolicy::class);
        Gate::policy(Lancamento::class, LancamentoPolicy::class);
        Gate::policy(FaturaCartao::class, FaturaCartaoPolicy::class);
        Gate::policy(Objetivo::class, ObjetivoPolicy::class);
        Gate::policy(Orcamento::class, OrcamentoPolicy::class);
        Gate::policy(OrcamentoServico::class, OrcamentoServicoPolicy::class);
        Model::shouldBeStrict(! app()->isProduction());
    }
}
