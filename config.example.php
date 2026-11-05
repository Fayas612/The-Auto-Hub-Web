<?php
return [
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=auto_hub;charset=utf8mb4',
    'user' => 'root',
    'password' => '',
    // Optional booking SMS alerts. Secrets belong in environment variables.
    'sms' => [
        'enabled' => false,
        'provider' => 'twilio',
        'recipient' => '+94777278078',
        'account_sid' => getenv('TWILIO_ACCOUNT_SID') ?: '',
        'auth_token' => getenv('TWILIO_AUTH_TOKEN') ?: '',
        'from' => getenv('TWILIO_FROM_NUMBER') ?: '',
    ],
    // Staff accounts are stored in MySQL. First setup:
    // php tools/create-super-admin.php
];
