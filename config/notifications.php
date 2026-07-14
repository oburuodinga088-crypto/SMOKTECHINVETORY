<?php
// Notification settings — update with real SMTP or admin email for production.
return [
    'admin_email' => 'admin@example.com',
    // If using a local mail server, leave smtp settings empty; otherwise configure.
    'smtp' => [
        'host' => '',
        'port' => 25,
        'username' => '',
        'password' => '',
        'encryption' => '', // 'tls' or 'ssl'
    ],
    'from_email' => 'no-reply@example.com',
    'from_name' => 'SmokeTech IMS',
];
