<?php

namespace YrGroup\LaravelZatca\Services;

use Salla\ZATCA\Helpers\Certificate;
use Salla\ZATCA\Models\InvoiceSign;

class ZatcaInvoiceService
{
    /**
     * Sign invoice XML and generate QR code
     *
     * @param string $xmlInvoice         Invoice XML content
     * @param string $certificateContent Base64 encoded certificate from ZATCA
     * @param string $privateKeyPath     Path to private key file
     * @param string $secret             Secret key from ZATCA
     *
     * @return array Returns hash, signed invoice, and QR code
     *
     * @throws \Exception
     */
    public function signInvoice(string $xmlInvoice, string $certificateContent, string $privateKeyPath, string $secret): array
    {
        // Validate private key file exists
        if (!file_exists($privateKeyPath)) {
            throw new \Exception("Private key file not found at: {$privateKeyPath}");
        }

        // Create certificate object
        $certificate = (new Certificate(
            base64_decode($certificateContent), // Certificate from ZATCA
            file_get_contents($privateKeyPath)  // Private key from CSR generation
        ))->setSecretKey($secret);              // Secret from ZATCA

        // Sign the invoice
        $invoice = (new InvoiceSign($xmlInvoice, $certificate))->sign();

        return [
            'hash' => $invoice->getHash(),
            'signed_invoice' => $invoice->getInvoice(),
            'qr_code' => $invoice->getQRCode(), // Base64 QR code
        ];
    }
}
