<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZATCA Environment
    |--------------------------------------------------------------------------
    |
    | This value determines which ZATCA environment your application is using.
    | Use 'simulation' for testing and 'production' for live transactions.
    |
    | Available: 'simulation', 'production'
    |
    */
    'environment' => env('ZATCA_ENVIRONMENT', 'simulation'),

    /*
    |--------------------------------------------------------------------------
    | ZATCA OTP (One-Time Password)
    |--------------------------------------------------------------------------
    |
    | The OTP obtained from ZATCA portal for compliance certificate generation.
    | Get it from: https://fatoora.zatca.gov.sa/ (Production)
    | Or: https://fatoora-simulation.zatca.gov.sa/ (Simulation)
    |
    */
    'otp' => env('ZATCA_OTP'),

    /*
    |--------------------------------------------------------------------------
    | Solution Information
    |--------------------------------------------------------------------------
    |
    | Your ERP/POS solution name and version number.
    | These are required for CSR generation.
    |
    */
    'solution_name' => env('ZATCA_SOLUTION_NAME', 'MyERP'),
    'version' => env('ZATCA_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Certificate Storage Paths
    |--------------------------------------------------------------------------
    |
    | Default paths for storing ZATCA certificates and keys.
    | It's recommended to store these in the database for production use.
    |
    */
    'certificate_path' => storage_path('app/zatca/certificate.txt'),
    'private_key_path' => storage_path('app/zatca/private_key.pem'),
    'secret_path' => storage_path('app/zatca/secret.txt'),

    'test_mode_certificate' => [
        'reporting' => [
            'username' => env('ZATCA_TEST_MODE_REPORTING_USERNAME'),
            'password' => env('ZATCA_TEST_MODE_REPORTING_PASSWORD'),
        ]
    ]

];
