<?php

namespace YrGroup\LaravelZatca\Services;

use Illuminate\Support\Facades\Http;

class ZatcaAPIService
{
    protected string $baseUrl;

    public function __construct()
    {
        // ZATCA has two environments:
        // 1. Simulation (developer-portal) - for initial testing
        // 2. Production (core) - for live transactions

        $environment = config('zatca.environment', 'simulation');

        if ($environment === 'production') {
            $this->baseUrl = 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core';
        } else {
            // Use simulation environment for testing
            $this->baseUrl = 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal';
        }
    }

    /**
     * Step 1: Get Compliance CSID (Onboarding)
     * Endpoint: /compliance
     *
     * @param string $csrContent The CSR content generated in previous step
     * @param string $otp        One-Time Password from ZATCA portal
     *
     * @return array Returns request ID, disposition message, certificate and secret
     *
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Exception
     */
    public function getComplianceCSID(string $csrContent, string $otp): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'otp' => $otp,
            'Accept-Version' => 'V2',
        ])->post($this->baseUrl . '/compliance', [
            'csr' => base64_encode($csrContent),
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'request_id' => $data['requestID'],
                'disposition_message' => $data['dispositionMessage'],
                'binary_security_token' => $data['binarySecurityToken'], // Certificate
                'secret' => $data['secret'], // Secret key for signing
            ];
        }

        throw new \Exception('Failed to get compliance CSID: ' . $response->body());
    }

    /**
     * Step 2: Compliance Check - Submit sample invoices for testing
     * Endpoint: /compliance/invoices
     *
     * @param string $signedInvoice Base64 encoded signed invoice XML
     * @param string $invoiceHash   Invoice hash
     * @param string $uuid          Invoice UUID
     * @param string $certificate   Compliance certificate
     * @param string $secret        Compliance secret
     *
     * @return array ZATCA response
     *
     * @throws \Exception
     */
    public function checkInvoiceCompliance(string $signedInvoice, string $invoiceHash, string $uuid, string $certificate, string $secret): array
    {
        $encodedInvoice = base64_encode($signedInvoice);
        $encodedHash = base64_encode($invoiceHash);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'Accept-Version' => 'V2',
            'Authorization' => 'Basic ' . base64_encode($certificate . ':' . $secret),
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/compliance/invoices', [
            'invoiceHash' => $encodedHash,
            'uuid' => $uuid,
            'invoice' => $encodedInvoice,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Compliance check failed: ' . $response->body());
    }

    /**
     * Step 3: Get Production CSID
     * Endpoint: /production/csids
     *
     * @param string $complianceRequestId Request ID from compliance CSID
     * @param string $certificate         Compliance certificate
     * @param string $secret              Compliance secret
     *
     * @return array Returns production certificate and secret
     *
     * @throws \Exception
     */
    public function getProductionCSID(string $complianceRequestId, string $certificate, string $secret): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Accept-Version' => 'V2',
            'Authorization' => 'Basic ' . base64_encode($certificate . ':' . $secret),
        ])->post($this->baseUrl . '/production/csids', [
            'compliance_request_id' => $complianceRequestId,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'request_id' => $data['requestID'],
                'binary_security_token' => $data['binarySecurityToken'], // Production certificate
                'secret' => $data['secret'], // Production secret
            ];
        }

        throw new \Exception('Failed to get production CSID: ' . $response->body());
    }

    /**
     * Step 4: Report Invoice (for standard invoices - B2B)
     * Endpoint: /invoices/reporting/single
     *
     * @param string $signedInvoice Signed invoice XML
     * @param string $invoiceHash   Invoice hash
     * @param string $uuid          Invoice UUID
     * @param string $certificate   Production certificate
     * @param string $secret        Production secret
     *
     * @return array ZATCA response
     *
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Exception
     */
    public function reportInvoice(string $signedInvoice, string $invoiceHash, string $uuid, string $certificate, string $secret): array
    {
        $encodedInvoice = base64_encode($signedInvoice);
        $encodedHash = base64_encode($invoiceHash);

        // Use test mode certificate for simulation environment
        if (config('zatca.environment') === 'simulation') {
            $certificate = config('zatca.test_mode_certificate.reporting.username');
            $secret = config('zatca.test_mode_certificate.reporting.password');
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'Accept-Version' => 'V2',
            'Authorization' => 'Basic ' . base64_encode($certificate . ':' . $secret),
            'Content-Type' => 'application/json',
            'Clearance-Status' => '1',
        ])->post($this->baseUrl . '/invoices/reporting/single', [
            'invoiceHash' => $encodedHash,
            'uuid' => $uuid,
            'invoice' => $encodedInvoice,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Invoice reporting failed: ' . $response->body());
    }

    /**
     * Step 4b: Clear Invoice (for simplified invoices - B2C)
     * Endpoint: /invoices/clearance/single
     *
     * @param string $signedInvoice Signed invoice XML
     * @param string $invoiceHash   Invoice hash
     * @param string $uuid          Invoice UUID
     * @param string $certificate   Production certificate
     * @param string $secret        Production secret
     *
     * @return array ZATCA response with cleared invoice
     *
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Exception
     */
    public function clearInvoice(string $signedInvoice, string $invoiceHash, string $uuid, string $certificate, string $secret): array
    {
        $encodedInvoice = base64_encode($signedInvoice);
        $encodedHash = base64_encode($invoiceHash);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'Accept-Version' => 'V2',
            'Authorization' => 'Basic ' . base64_encode($certificate . ':' . $secret),
            'Content-Type' => 'application/json',
            'Clearance-Status' => '1',
        ])->post($this->baseUrl . '/invoices/clearance/single', [
            'invoiceHash' => $encodedHash,
            'uuid' => $uuid,
            'invoice' => $encodedInvoice,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Invoice clearance failed: ' . $response->body());
    }
}
