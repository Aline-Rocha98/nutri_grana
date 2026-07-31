<?php

namespace App\Providers;

use App\Models\CartaoCredito\CartaoCredito;
use App\Models\Categoria\Categoria;
use App\Models\ContaBancaria\ContaBancaria;
use App\Policies\CartaoCredito\CartaoCreditoPolicy;
use App\Policies\Categoria\CategoriaPolicy;
use App\Policies\ContaBancaria\ContaBancariaPolicy;
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
        Model::shouldBeStrict(! app()->isProduction());
    }
}
