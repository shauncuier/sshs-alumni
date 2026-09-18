<?php

declare(strict_types=1);

use App\Services\Payments\Gateways\ManualGateway;

return [

    /*
    |---------------------------------------------------------------------
    | Default gateway
    |---------------------------------------------------------------------
    |
    | `manual` is the only implementation today, and it is not a placeholder:
    | the association collects most of its money in cash, by bank transfer,
    | and through mobile financial services settled outside the platform.
    | Recording that properly is the primary flow, not a fallback.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'manual'),

    'currency' => env('PAYMENT_CURRENCY', 'BDT'),

    /*
    |---------------------------------------------------------------------
    | Gateways
    |---------------------------------------------------------------------
    |
    | Adding bKash, Nagad or SSLCommerz later is one class implementing
    | App\Services\Payments\Contracts\PaymentGateway plus one entry here.
    | No controller, service or component changes.
    |
    */

    'gateways' => [
        'manual' => [
            'driver' => ManualGateway::class,
        ],

        // 'bkash' => [
        //     'driver' => BkashGateway::class,
        //     'app_key' => env('BKASH_APP_KEY'),
        //     'app_secret' => env('BKASH_APP_SECRET'),
        //     'sandbox' => env('BKASH_SANDBOX', true),
        // ],
    ],

    /*
    |---------------------------------------------------------------------
    | Document numbering
    |---------------------------------------------------------------------
    |
    | Receipts are issued on payment; invoices are issued for sponsorships,
    | which are invoiced before payment. Both stay in Latin digits so they
    | can be quoted over the phone and searched reliably.
    |
    */

    'receipt_prefix' => env('PAYMENT_RECEIPT_PREFIX', 'RCP'),
    'invoice_prefix' => env('PAYMENT_INVOICE_PREFIX', 'INV'),

];
