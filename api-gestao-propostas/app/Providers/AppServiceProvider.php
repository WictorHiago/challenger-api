<?php

namespace App\Providers;

use App\Contracts\ClienteRepositoryInterface;
use App\Contracts\PropostaAuditoriaInterface;
use App\Contracts\PropostaRepositoryInterface;
use App\Repositories\ClienteRepository;
use App\Repositories\PropostaRepository;
use App\Services\PropostaAuditoriaService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ClienteRepositoryInterface::class, ClienteRepository::class);
        $this->app->bind(PropostaRepositoryInterface::class, PropostaRepository::class);
        $this->app->bind(PropostaAuditoriaInterface::class, PropostaAuditoriaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
