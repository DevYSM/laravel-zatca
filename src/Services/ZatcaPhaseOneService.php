<?php

namespace YrGroup\LaravelZatca\Services;

use Prgayman\Zatca\Facades\Zatca;
use Prgayman\Zatca\Utilis\QrCodeOptions;

class ZatcaPhaseOneService
{
    protected ?string $sellerName = null;

    protected ?string $vatNumber = null;

    protected mixed $timestamp = null;

    protected ?string $totalWithVat = null;

    protected ?string $vatTotal = null;

    /**
     * Create a new instance
     */
    public static function make(): self
    {
        return new self;
    }

    /**
     * Set a VAT registration number
     */
    public function vatNumber(string $vatNumber): self
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    /**
     * Generate QR code with custom options
     */
    public function generate(?QrCodeOptions $options = null): string
    {
        // Use default options if not provided
        if (!$options) {
            $options = (new QrCodeOptions)
                ->format('svg')
                ->color(0, 0, 0)        // black foreground
                ->backgroundColor(255, 255, 255) // white background
                ->size(100)
                ->margin(0)
                ->style('square', 0.9)
                ->eye('square');
        }

        return Zatca::sellerName($this->sellerName ?? config('app.name'))
            ->vatRegistrationNumber($this->vatNumber ?? '123456789123456')
            ->timestamp($this->timestamp ?? now())
            ->totalWithVat($this->totalWithVat ?? '0.00')
            ->vatTotal($this->vatTotal ?? '0.00')
            ->toQrCode($options);
    }

    /**
     * Set VAT total
     */
    public function vatTotal(string $vat): self
    {
        $this->vatTotal = $vat;

        return $this;
    }

    /**
     * Set total amount with VAT
     */
    public function totalWithVat(string $total): self
    {
        $this->totalWithVat = $total;

        return $this;
    }

    /**
     * Set timestamp
     */
    public function timestamp($timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Set the seller name
     */
    public function sellerName(string $name): self
    {
        $this->sellerName = $name;

        return $this;
    }

    /**
     * Generate QR code as base64 PNG
     */
    public function generateBase64(): string
    {
        $options = (new QrCodeOptions)
            ->format('png')
            ->color(0, 0, 0)
            ->backgroundColor(255, 255, 255)
            ->size(100)
            ->margin(0);

        return Zatca::sellerName($this->sellerName ?? config('app.name'))
            ->vatRegistrationNumber($this->vatNumber ?? '123456789123456')
            ->timestamp($this->timestamp ?? now())
            ->totalWithVat($this->totalWithVat ?? '0.00')
            ->vatTotal($this->vatTotal ?? '0.00')
            ->toQrCode($options);
    }
}
