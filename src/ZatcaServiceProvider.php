<?php

namespace YrGroup\LaravelZatca;

use Illuminate\Support\ServiceProvider;
use YrGroup\LaravelZatca\Services\ZatcaAPIService;
use YrGroup\LaravelZatca\Services\ZatcaInvoiceService;
use YrGroup\LaravelZatca\Services\ZatcaPhaseOneService;
use YrGroup\LaravelZatca\Services\ZatcaService;

class ZatcaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config with application config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/zatca.php',
            'zatca'
        );

        // Register services as singletons
        $this->app->singleton(ZatcaService::class, function ($app) {
            return new ZatcaService;
        });

        $this->app->singleton(ZatcaAPIService::class, function ($app) {
            return new ZatcaAPIService;
        });

        $this->app->singleton(ZatcaInvoiceService::class, function ($app) {
            return new ZatcaInvoiceService;
        });

        $this->app->singleton(ZatcaPhaseOneService::class, function ($app) {
            return new ZatcaPhaseOneService;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish a config file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/zatca.php' => config_path('zatca.php'),
            ], 'zatca-config');
        }
    }
}
