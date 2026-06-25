<?php

return [
    'base_url'      => env('SSO_BASE_URL', 'http://localhost:8000'),
    'client_id'     => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'mock'          => env('SSO_MOCK', true),
    'mock_email'    => env('SSO_MOCK_EMAIL', 'phanuwat@live.uru.ac.th'),
    'mock_password' => env('SSO_MOCK_PASSWORD', 'password'),
];
