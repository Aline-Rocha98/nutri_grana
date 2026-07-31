<?php

namespace App\Providers;

use App\Models\CartoesCredito\CartaoCredito;
use App\Models\ContasBancarias\ContaBancaria;
use App\Policies\CartoesCredito\CartaoCreditoPolicy;
use App\Policies\ContasBancarias\ContaBancariaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;

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
        Model::shouldBeStrict(!app()->isProduction());
    }
}
