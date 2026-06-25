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
        if (config('sso.mock')) {
            if (
                $email !== (string) config('sso.mock_email')
                || $password !== (string) config('sso.mock_password')
            ) {
                throw new \RuntimeException('Invalid mock SSO credentials.');
            }

            return [
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => 'mock-sso-access-token',
            ];
        }

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
        if (config('sso.mock')) {
            if ($accessToken !== 'mock-sso-access-token') {
                throw new \RuntimeException('Invalid mock SSO token.');
            }

            return $this->mockUserInfo();
        }

        $response = $this->http->get('/api/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept'        => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function mockUserInfo(): array
    {
        return [
            'id' => 2174,
            'code' => '205234',
            'type' => 'TEACHER',
            'degree' => 'MASTER',
            'prefix_en' => 'Mr.',
            'first_name_en' => 'Phanuwat ',
            'last_name_en' => 'khanwat',
            'prefix_th' => 'นาย',
            'first_name_th' => 'ภานุวัฒน์ ',
            'last_name_th' => 'ขันจา',
            'gender' => 'MALE',
            'faculty_id' => 5,
            'faculty_name_en' => 'คณะเทคโนโลยีอุตสาหกรรม',
            'faculty_name_th' => 'คณะเทคโนโลยีอุตสาหกรรม',
            'department_id' => 122,
            'department_name_en' => 'หลักสูตรวิศวกรรมบัณฑิต',
            'department_name_th' => 'หลักสูตรวิศวกรรมบัณฑิต',
            'study_year' => 0,
            'nickname' => '',
            'email' => config('sso.mock_email'),
            'status' => 'ACTIVE',
            'nationality' => 'THAI',
            'picture' => 'https://hrms.uru.ac.th/WebURUINF/Applications/File/Forms/frmFileStorage?guid=46407332252C4D439A5383C2A07257EA',
            'picture_base64' => '',
            'birth_date' => '1980-04-10',
            'last_updated_at' => '2026-04-07 06:12:29',
            'teacher' => [],
            'study' => [],
            'custom1' => null,
            'custom2' => null,
            'custom3' => null,
            'campus_id' => 1,
            'curriculum_id' => 0,
            'username' => 'phanuwat',
            'citizen_id' => '555',
            'passport_id' => '',
        ];
    }
}
