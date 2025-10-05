<?php

namespace YrGroup\LaravelZatca\Facades;

use Illuminate\Support\Facades\Facade;
use YrGroup\LaravelZatca\Services\ZatcaAPIService;
use YrGroup\LaravelZatca\Services\ZatcaInvoiceService;
use YrGroup\LaravelZatca\Services\ZatcaPhaseOneService;
use YrGroup\LaravelZatca\Services\ZatcaService;

/**
 * ZATCA Facade for easy access to all services
 */
class Zatca extends Facade
{
    /**
     * Get CSR service instance
     */
    public static function csr(): ZatcaService
    {
        return app(ZatcaService::class);
    }

    /**
     * Get API service instance
     */
    public static function api(): ZatcaAPIService
    {
        return app(ZatcaAPIService::class);
    }

    /**
     * Get Invoice service instance
     */
    public static function invoice(): ZatcaInvoiceService
    {
        return app(ZatcaInvoiceService::class);
    }

    /**
     * Get Phase One service instance
     */
    public static function phaseOne(): ZatcaPhaseOneService
    {
        return app(ZatcaPhaseOneService::class);
    }

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'zatca';
    }
}
