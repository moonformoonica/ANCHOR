<?php

namespace App\Providers;

use App\Contracts\DistressCheckServiceInterface;
use App\Contracts\HostilityScoringServiceInterface;
use App\Contracts\LegalClassificationServiceInterface;
use App\Services\StubDistressCheckService;
use App\Services\StubHostilityScoringService;
use App\Services\StubLegalClassificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LegalClassificationServiceInterface::class, StubLegalClassificationService::class);
        $this->app->bind(HostilityScoringServiceInterface::class, StubHostilityScoringService::class);
        $this->app->bind(DistressCheckServiceInterface::class, StubDistressCheckService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
