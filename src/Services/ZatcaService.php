<?php

namespace YrGroup\LaravelZatca\Services;

use Salla\ZATCA\GenerateCSR;
use Salla\ZATCA\Models\CSRRequest;

class ZatcaService
{
    /**
     * Generate Certificate Signing Request (CSR) for ZATCA
     *
     * @param array $merchantData Array containing merchant information
     *
     * @return array Returns CSR content and file paths
     *
     * @throws \Salla\ZATCA\Exception\CSRValidationException
     * @throws \Exception
     */
    public function generateCSR(array $merchantData): array
    {
        // Prepare CSR request
        $csrRequest = CSRRequest::make()
            ->setUID($merchantData['vat_number']) // Organization VAT number
            ->setSerialNumber(
                $merchantData['solution_name'],    // e.g., "MyERP"
                $merchantData['version'],          // e.g., "1.0.0"
                $merchantData['serial_number']     // Unique device serial
            )
            ->setCommonName($merchantData['common_name'])
            ->setCountryName('SA')
            ->setOrganizationName($merchantData['organization_name'])
            ->setOrganizationalUnitName($merchantData['organizational_unit'])
            ->setRegisteredAddress($merchantData['registered_address'])
            ->setInvoiceType(true, true) // (standard, simplified) - adjust based on your needs
            ->setCurrentZatcaEnv(config('zatca.environment', 'simulation'))
            ->setBusinessCategory($merchantData['business_category']);

        // Generate CSR
        $csr = GenerateCSR::fromRequest($csrRequest)->initialize()->generate();

        // Store private key securely (IMPORTANT!)
        $privateKeyPath = storage_path('app/zatca/private_key.pem');
        
        if (!file_exists(dirname($privateKeyPath)))
            mkdir(dirname($privateKeyPath), 0755, true);

        openssl_pkey_export_to_file($csr->getPrivateKey(), $privateKeyPath);

        // Store CSR content
        $csrPath = storage_path('app/zatca/csr.pem');
        file_put_contents($csrPath, $csr->getCsrContent());

        return [
            'csr_content' => $csr->getCsrContent(),
            'private_key_path' => $privateKeyPath,
            'csr_path' => $csrPath,
        ];
    }
}
