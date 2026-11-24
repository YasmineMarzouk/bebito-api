<?php

return [
    'admin' => [
        'session_cookie' => env('ADMIN_SESSION_COOKIE', 'bebito-admin-session'),
        'session_table' => env('ADMIN_SESSION_TABLE', 'admin_sessions'),
        'xsrf_cookie' => env('ADMIN_XSRF_COOKIE', 'XSRF-ADMIN-TOKEN'),
        'origins' => array_filter(explode(',', env('ADMIN_FRONTEND_URLS', 'http://localhost:3000'))),
    ],

    'customer' => [
        'session_cookie' => env('CUSTOMER_SESSION_COOKIE', 'bebito-customer-session'),
        'session_table' => env('CUSTOMER_SESSION_TABLE', 'customer_sessions'),
        'xsrf_cookie' => env('CUSTOMER_XSRF_COOKIE', 'XSRF-CUSTOMER-TOKEN'),
        'origins' => array_filter(explode(',', env('CUSTOMER_FRONTEND_URLS', 'http://localhost:4173'))),
    ],
];
