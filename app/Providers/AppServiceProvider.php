<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
        // 🔹 Pakai style pagination Bootstrap 5, cocok dengan Skydash
        Paginator::useBootstrapFive();
        // Kalau pakai Bootstrap 4:
        Paginator::useBootstrapFour();
    }
}
