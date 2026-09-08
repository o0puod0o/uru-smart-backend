<?php

return [
    // Used only by InitialAdminAccountSeeder during first production setup.
    // The value is never used as an application login credential at runtime.
    'bootstrap' => [
        'username' => env('ADMIN_BOOTSTRAP_USERNAME'),
        'password' => env('ADMIN_BOOTSTRAP_PASSWORD'),
        'name' => env('ADMIN_BOOTSTRAP_NAME', 'System Administrator'),
        'email' => env('ADMIN_BOOTSTRAP_EMAIL'),
    ],
];
