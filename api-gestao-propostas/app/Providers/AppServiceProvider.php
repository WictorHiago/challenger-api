<?php

namespace App\Providers;

use App\Contracts\ClienteRepositoryInterface;
use App\Repositories\ClienteRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ClienteRepositoryInterface::class, ClienteRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
