<?php

return [
    'database_seeders' => [
        // App\Database\Seeders\UserSeeder::class,
        // App\Database\Seeders\RoleSeeder::class,
    ],
    'smtpData' => [
        'dailyLimit' => 500,
        'accounts' => [
            [
                'name' => Str::before(env('MAIL_USERNAME_1'), '@'),
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => env('MAIL_USERNAME_1'),
                'password' => env('MAIL_PASSWORD_1'),
            ]
        ]
    ]
];
