<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SSOService
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => trim(config('sso.base_url')),
            'timeout'  => 10,
        ]);
    }

    // ส่ง email + password ไปขอ token จาก SSO โดยตรง
    public function loginWithPassword(string $email, string $password): array
    {
        $response = $this->http->post('/oauth/token', [
            'form_params' => [
                'grant_type'    => 'password',
                'client_id'     => trim((string) config('sso.client_id')),
                'client_secret' => trim((string) config('sso.client_secret')),
                'username'      => $email,
                'password'      => $password,
                'scope'         => 'view-profile view-employee',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    // เอา access_token ไปดึงข้อมูลอาจารย์
    public function getUserInfo(string $accessToken): array
    {
        $response = $this->http->get('/api/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept'        => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}