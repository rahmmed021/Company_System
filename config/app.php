<?php
return [
    'name' => env('APP_NAME', 'Naoshin Enterprise'),
    'env' => env('APP_ENV', 'local'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'https://nousinenterprise.com/'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Dhaka'),
    'default_language' => env('DEFAULT_LANGUAGE', 'bn'),
    'currency' => env('CURRENCY', 'BDT'),
    'session_timeout' => (int) env('SESSION_TIMEOUT', 7200),
];
