# Laravel ZATCA - Usage Examples

This package makes ZATCA integration incredibly easy! Here are quick examples:

## Phase 1: QR Code Generation (Super Easy!)

```php
use BrandMeOn\LaravelZatca\Facades\Zatca;

// Generate QR code in just 5 lines!
$qrCode = Zatca::phaseOne()
    ->sellerName('My Company')
    ->vatNumber('123456789123456')
    ->timestamp(now())
    ->totalWithVat('115.00')
    ->vatTotal('15.00')
    ->generate();

// Display in your blade template
<div>{!! $qrCode !!}</div>
```

## Phase 2: Full E-Invoicing (Step by Step)

### Step 1: Generate CSR (One-time setup)

```php
use BrandMeOn\LaravelZatca\Facades\Zatca;

$result = Zatca::csr()->generateCSR([
    'vat_number' => '310461435700003',
    'solution_name' => config('zatca.solution_name'),
    'version' => config('zatca.version'),
    'serial_number' => '1-TST|2-TST|3-' . uniqid(),
    'common_name' => 'TST-886431145-399999999900003',
    'organization_name' => 'My Company',
    'organizational_unit' => 'Riyad Branch',
    'registered_address' => 'Riyadh',
    'business_category' => 'Retail',
]);
```

### Step 2: Get Certificate (One-time setup)

```php
// Get OTP from ZATCA portal first
$otp = '123456';
$csrContent = file_get_contents(storage_path('app/zatca/csr.pem'));

$result = Zatca::api()->getComplianceCSID($csrContent, $otp);

// Save for later use
file_put_contents(storage_path('app/zatca/certificate.txt'), $result['binary_security_token']);
file_put_contents(storage_path('app/zatca/secret.txt'), $result['secret']);
```

### Step 3: Sign & Submit Invoice

```php
// Load your XML invoice
$xmlInvoice = file_get_contents(storage_path('app/zatca/invoices/invoice.xml'));

// Sign it
$signedData = Zatca::invoice()->signInvoice(
    $xmlInvoice,
    file_get_contents(storage_path('app/zatca/certificate.txt')),
    storage_path('app/zatca/private_key.pem'),
    file_get_contents(storage_path('app/zatca/secret.txt'))
);

// Submit to ZATCA
$uuid = 'invoice-uuid-here';
$result = Zatca::api()->reportInvoice(
    $signedData['signed_invoice'],
    $signedData['hash'],
    $uuid,
    file_get_contents(storage_path('app/zatca/certificate.txt')),
    file_get_contents(storage_path('app/zatca/secret.txt'))
);

// Done! Show the QR code
echo $signedData['qr_code'];
```

## Using Dependency Injection

```php
use BrandMeOn\LaravelZatca\Services\ZatcaPhaseOneService;
use BrandMeOn\LaravelZatca\Services\ZatcaAPIService;

class InvoiceController extends Controller
{
    public function __construct(
        protected ZatcaPhaseOneService $zatcaPhaseOne,
        protected ZatcaAPIService $zatcaApi
    ) {}

    public function generateQR()
    {
        $qrCode = $this->zatcaPhaseOne
            ->sellerName('My Company')
            ->vatNumber('123456789123456')
            ->timestamp(now())
            ->totalWithVat('115.00')
            ->vatTotal('15.00')
            ->generate();
            
        return view('invoice.show', compact('qrCode'));
    }
}
```

## That's it!

The package handles all the complexity of ZATCA integration, giving you a clean and simple API to work with.
