<?php

return [
    'letterhead' => env('INVOICE_LETTERHEAD', 'assets/img/invoice-letterhead.png'),

    'safe_area' => [
        'top' => env('INVOICE_SAFE_TOP', '30mm'),
        'bottom' => env('INVOICE_SAFE_BOTTOM', '35mm'),
        'left' => env('INVOICE_SAFE_LEFT', '18mm'),
        'right' => env('INVOICE_SAFE_RIGHT', '18mm'),
    ],

    'bank' => [
        'account_name' => env('INVOICE_BANK_ACCOUNT_NAME', 'WAKA LINE LOGISTICS LIMITED'),
        'account_number' => env('INVOICE_BANK_ACCOUNT_NUMBER', '0127359296'),
        'bank_name' => env('INVOICE_BANK_NAME', 'WEMA BANK'),
    ],
];
