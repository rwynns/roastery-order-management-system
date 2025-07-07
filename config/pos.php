<?php

return [
    'default_tax_rate' => env('POS_DEFAULT_TAX_RATE', 10), // 10%
    'currency' => env('POS_CURRENCY', 'IDR'),
    'currency_symbol' => env('POS_CURRENCY_SYMBOL', 'Rp'),
    
    'order_statuses' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ],
    
    'payment_statuses' => [
        'unpaid' => 'Unpaid',
        'partial' => 'Partially Paid',
        'paid' => 'Fully Paid',
        'refunded' => 'Refunded'
    ],
    
    'payment_methods' => [
        'cash' => 'Cash',
        'card' => 'Credit/Debit Card',
        'transfer' => 'Bank Transfer',
        'ewallet' => 'E-Wallet'
    ]
];