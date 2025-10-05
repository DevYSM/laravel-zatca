# Laravel ZATCA Package

Easy-to-use Laravel package for ZATCA (Saudi Arabian E-Invoicing) Phase 1 and Phase 2 integration.

## Features

- ✅ **Phase 1**: Simple QR Code generation for invoices
- ✅ **Phase 2**: Full E-Invoicing integration with ZATCA
    - CSR (Certificate Signing Request) generation
    - Compliance CSID acquisition
    - Production CSID acquisition
    - Invoice signing and submission
    - Support for both B2B (reporting) and B2C (clearance) invoices

## Installation

1. Require the package

```bash
composer require yr-group/laravel-zatca
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --tag=zatca-config
```

3. Configure your `.env` file:

```env
ZATCA_ENVIRONMENT=simulation  # or 'production'
ZATCA_SOLUTION_NAME=MyERP
ZATCA_VERSION=1.0.0
```

## Usage

### Phase 1: QR Code Generation

Generate simple QR codes for invoices (for Phase 1 compliance):

```php
use BrandMeOn\LaravelZatca\Facades\Zatca;

// Generate QR code
$qrCode = Zatca::phaseOne()
    ->sellerName('My Company')
    ->vatNumber('123456789123456')
    ->timestamp(now())
    ->totalWithVat('115.00')
    ->vatTotal('15.00')
    ->generate();

// Generate as base64 PNG
$qrCodeBase64 = Zatca::phaseOne()
    ->sellerName('My Company')
    ->vatNumber('123456789123456')
    ->timestamp(now())
    ->totalWithVat('115.00')
    ->vatTotal('15.00')
    ->generateBase64();
```

### Phase 2: Full E-Invoicing Integration

#### Step 1: Generate CSR (Certificate Signing Request)

```php
use BrandMeOn\LaravelZatca\Facades\Zatca;

$merchantData = [
    'vat_number' => '310461435700003',
    'solution_name' => config('zatca.solution_name'),
    'version' => config('zatca.version'),
    'serial_number' => '1-TST|2-TST|3-' . uniqid(),
    'common_name' => 'TST-886431145-399999999900003',
    'organization_name' => 'My Test Company',
    'organizational_unit' => 'Riyad Branch',
    'registered_address' => 'Riyadh',
    'business_category' => 'Retail',
];

$result = Zatca::csr()->generateCSR($merchantData);

// Returns:
// [
//     'csr_content' => '...',
//     'private_key_path' => '/path/to/private_key.pem',
//     'csr_path' => '/path/to/csr.pem'
// ]
```

#### Step 2: Get Compliance Certificate

Get an OTP from the ZATCA portal:

- Simulation: https://fatoora-simulation.zatca.gov.sa/
- Production: https://fatoora.zatca.gov.sa/

```php
$csrContent = file_get_contents(storage_path('app/zatca/csr.pem'));
$otp = '123456'; // From ZATCA portal

$result = Zatca::api()->getComplianceCSID($csrContent, $otp);

// Save the certificate and secret
file_put_contents(storage_path('app/zatca/certificate.txt'), $result['binary_security_token']);
file_put_contents(storage_path('app/zatca/secret.txt'), $result['secret']);
```

#### Step 3: Sign and Submit Invoice

```php
// Load your XML invoice (must conform to ZATCA UBL 2.1 format)
$xmlInvoice = file_get_contents(storage_path('app/zatca/invoices/invoice.xml'));

// Load certificate and secret
$certificate = file_get_contents(storage_path('app/zatca/certificate.txt'));
$secret = file_get_contents(storage_path('app/zatca/secret.txt'));
$privateKeyPath = storage_path('app/zatca/private_key.pem');

// Sign the invoice
$signedData = Zatca::invoice()->signInvoice(
    $xmlInvoice,
    $certificate,
    $privateKeyPath,
    $secret
);

// Extract UUID from XML
$doc = new \DOMDocument();
$doc->loadXML($xmlInvoice);
$xpath = new \DOMXPath($doc);
$uuid = $xpath->query('//cbc:UUID')->item(0)->nodeValue;

// Report invoice (B2B - Standard invoices)
$result = Zatca::api()->reportInvoice(
    $signedData['signed_invoice'],
    $signedData['hash'],
    $uuid,
    $certificate,
    $secret
);

// Or clear invoice (B2C - Simplified invoices)
$result = Zatca::api()->clearInvoice(
    $signedData['signed_invoice'],
    $signedData['hash'],
    $uuid,
    $certificate,
    $secret
);
```

#### Step 4: Get Production CSID (After Compliance Testing)

```php
$complianceRequestId = 'request-id-from-compliance';
$certificate = file_get_contents(storage_path('app/zatca/certificate.txt'));
$secret = file_get_contents(storage_path('app/zatca/secret.txt'));

$result = Zatca::api()->getProductionCSID(
    $complianceRequestId,
    $certificate,
    $secret
);

// Save production certificate and secret
file_put_contents(storage_path('app/zatca/prod_certificate.txt'), $result['binary_security_token']);
file_put_contents(storage_path('app/zatca/prod_secret.txt'), $result['secret']);
```

## API Reference

### ZatcaPhaseOneService

```php
Zatca::phaseOne()
    ->sellerName(string $name)
    ->vatNumber(string $vatNumber)
    ->timestamp($timestamp)
    ->totalWithVat(string $total)
    ->vatTotal(string $vat)
    ->generate(?QrCodeOptions $options = null): string
    ->generateBase64(): string
```

### ZatcaService (CSR Generation)

```php
Zatca::csr()->generateCSR(array $merchantData): array
```

### ZatcaAPIService

```php
Zatca::api()->getComplianceCSID(string $csrContent, string $otp): array
Zatca::api()->checkInvoiceCompliance(string $signedInvoice, string $invoiceHash, string $uuid, string $certificate, string $secret): array
Zatca::api()->getProductionCSID(string $complianceRequestId, string $certificate, string $secret): array
Zatca::api()->reportInvoice(string $signedInvoice, string $invoiceHash, string $uuid, string $certificate, string $secret): array
Zatca::api()->clearInvoice(string $signedInvoice, string $invoiceHash, string $uuid, string $certificate, string $secret): array
```

### ZatcaInvoiceService

```php
Zatca::invoice()->signInvoice(string $xmlInvoice, string $certificateContent, string $privateKeyPath, string $secret): array
```

## Configuration

The package uses the following configuration options (in `config/zatca.php`):

- `environment`: ZATCA environment (`simulation` or `production`)
- `otp`: One-Time Password from ZATCA portal
- `solution_name`: Your ERP/POS solution name
- `version`: Your solution version
- `certificate_path`: Path to store certificates
- `private_key_path`: Path to store private keys
- `secret_path`: Path to store secrets

## Requirements

- PHP 8.1 or higher
- Laravel 11.0 or higher
- salla/zatca package
- prgayman/laravel-zatca package

## License

MIT License

## Credits

Built with ❤️ by YR-Group <a href="https://yassensayed.com/">Yassen Sayed</a> & <a href="https://ramadanewais.com">
Ramadan Ewis</a>.
 
# laravel-zatca
# laravel-zatca
