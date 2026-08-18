<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SSOService
{
    protected Client $http;
    protected Client $expertApi;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => rtrim(trim((string) config('sso.base_url')), '/') . '/',
            'timeout'  => 10,
        ]);

        $this->expertApi = new Client([
            'base_uri' => rtrim(trim((string) config('sso.expert_api_base_url')), '/') . '/',
            'timeout'  => 10,
        ]);
    }

    public function loginWithPassword(string $email, string $password): array
    {
        if (config('sso.driver') === 'expert_profile') {
            if (config('sso.fast_mock')) {
                $account = $this->findMockAccountForLogin($email, $password);
                if (! $account) {
                    throw new \RuntimeException('Invalid fast mock SSO credentials.');
                }

                $idCard = (string) ($account['citizen_id'] ?? $account['username'] ?? $account['id']);
                $signature = hash_hmac('sha256', $idCard, (string) config('app.key'));

                return [
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                    'access_token' => "expert-profile-token.{$idCard}.{$signature}",
                ];
            }

            $idCard = $this->resolveExpertProfileIdCard($email);
            if (! $idCard && trim($password) !== '') {
                $oauthToken = $this->requestOauthToken($email, $password);
                $oauthUser = $this->requestOauthUserInfo($oauthToken['access_token']);
                $idCard = $this->extractIdCard($oauthUser);
            }

            if (! $idCard) {
                throw new \RuntimeException('Expert profile user not found.');
            }

            $signature = hash_hmac('sha256', $idCard, (string) config('app.key'));

            return [
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => "expert-profile-token.{$idCard}.{$signature}",
            ];
        }

        if (config('sso.local_database')) {
            $user = DB::connection('expert')
                ->table('users')
                ->where('username', $email)
                ->orWhere('email', $email)
                ->orWhere('id_card', $email)
                ->first();

            if (! $user || ! Hash::check($password, (string) $user->password)) {
                throw new \RuntimeException('Invalid local expert credentials.');
            }

            $payload = (string) $user->users_id;
            $signature = hash_hmac('sha256', $payload, (string) config('app.key'));

            return [
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => "local-expert-token.{$payload}.{$signature}",
            ];
        }

        if (config('sso.mock')) {
            $account = collect(config('sso.mock_accounts', []))->first(
                fn (array $account): bool => strcasecmp($account['email'], $email) === 0
                    && hash_equals((string) $account['password'], $password)
            );

            if (! $account) {
                throw new \RuntimeException('Invalid mock SSO credentials.');
            }

            $encodedEmail = rtrim(strtr(base64_encode(strtolower($account['email'])), '+/', '-_'), '=');
            $signature = hash_hmac('sha256', $encodedEmail, (string) config('app.key'));

            return [
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => "mock-sso-access-token.{$encodedEmail}.{$signature}",
            ];
        }

        return $this->requestOauthToken($email, $password);
    }

    public function getUserInfo(string $accessToken): array
    {
        if (config('sso.driver') === 'expert_profile') {
            return $this->expertProfileUserInfo($accessToken);
        }

        if (config('sso.local_database')) {
            return $this->localExpertUserInfo($accessToken);
        }

        if (config('sso.mock')) {
            $parts = explode('.', $accessToken, 3);
            if (count($parts) !== 3 || $parts[0] !== 'mock-sso-access-token') {
                throw new \RuntimeException('Invalid mock SSO token.');
            }

            [, $encodedEmail, $signature] = $parts;
            $expected = hash_hmac('sha256', $encodedEmail, (string) config('app.key'));
            if (! hash_equals($expected, $signature)) {
                throw new \RuntimeException('Invalid mock SSO token signature.');
            }

            $email = base64_decode(strtr($encodedEmail, '-_', '+/'), true);
            $account = collect(config('sso.mock_accounts', []))->first(
                fn (array $account): bool => strcasecmp($account['email'], (string) $email) === 0
            );
            if (! $account) {
                throw new \RuntimeException('Mock SSO account not found.');
            }

            return $this->mockUserInfo($account);
        }

        return $this->requestOauthUserInfo($accessToken);
    }

    private function expertProfileUserInfo(string $accessToken): array
    {
        $parts = explode('.', $accessToken, 3);

        if (config('sso.fast_mock') && count($parts) === 3 && $parts[0] === 'external-mock-sso-token') {
            [, $subject, $signature] = $parts;
            $expected = hash_hmac('sha256', $subject, (string) config('app.key'));
            if (! hash_equals($expected, $signature)) {
                throw new \RuntimeException('Invalid external mock SSO token signature.');
            }

            $account = $this->findMockAccountByIdentifier($subject);
            if (! $account) {
                throw new \RuntimeException('External mock SSO account not found.');
            }

            return $this->mockExpertProfile($account);
        }

        if (count($parts) !== 3 || $parts[0] !== 'expert-profile-token') {
            throw new \RuntimeException('Invalid expert profile token.');
        }

        [, $idCard, $signature] = $parts;
        $expected = hash_hmac('sha256', $idCard, (string) config('app.key'));
        if (! hash_equals($expected, $signature)) {
            throw new \RuntimeException('Invalid expert profile token signature.');
        }

        if (config('sso.fast_mock')) {
            $account = $this->findMockAccountByIdentifier($idCard);
            if (! $account) {
                throw new \RuntimeException('Fast mock expert profile not found.');
            }

            return $this->mockExpertProfile($account);
        }

        $apiToken = trim((string) config('sso.expert_api_token'));
        if ($apiToken === '') {
            throw new \RuntimeException('SSO expert API token is not configured.');
        }

        $response = $this->expertApi->get('experts/' . rawurlencode($idCard), [
            'query' => ['token' => $apiToken],
            'headers' => ['Accept' => 'application/json'],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function requestOauthToken(string $email, string $password): array
    {
        $response = $this->http->post('oauth/token', [
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

    public function exchangeAuthorizationCode(string $code, ?string $redirectUri = null): array
    {
        $response = $this->http->post('oauth/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => trim((string) config('sso.client_id')),
                'client_secret' => trim((string) config('sso.client_secret')),
                'redirect_uri' => $redirectUri ?: config('sso.redirect_uri'),
                'code' => $code,
            ],
            'headers' => ['Accept' => 'application/json'],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function requestOauthUserInfo(string $accessToken): array
    {
        $response = $this->http->get('api/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept'        => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function extractIdCard(array $payload): ?string
    {
        $idCard = $payload['citizen_id']
            ?? $payload['id_card']
            ?? $payload['data']['profile']['id_card']
            ?? null;

        return $idCard ? (string) $idCard : null;
    }

    private function resolveExpertProfileIdCard(string $identifier): ?string
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        if (preg_match('/^\d{13}$/', $identifier)) {
            return $identifier;
        }

        try {
            $user = DB::connection('expert')
                ->table('users')
                ->where('email', $identifier)
                ->orWhere('username', $identifier)
                ->orWhere('id_card', $identifier)
                ->first();

            return $user && $user->id_card ? (string) $user->id_card : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function findMockAccountForLogin(string $login, string $password): ?array
    {
        return collect(config('sso.mock_accounts', []))->first(
            fn (array $account): bool => (
                strcasecmp((string) ($account['email'] ?? ''), $login) === 0
                || strcasecmp((string) ($account['username'] ?? ''), $login) === 0
                || strcasecmp((string) ($account['citizen_id'] ?? ''), $login) === 0
            ) && hash_equals((string) ($account['password'] ?? ''), $password)
        );
    }

    private function findMockAccountByIdentifier(string $identifier): ?array
    {
        return collect(config('sso.mock_accounts', []))->first(
            fn (array $account): bool => strcasecmp((string) ($account['citizen_id'] ?? ''), $identifier) === 0
                || strcasecmp((string) ($account['username'] ?? ''), $identifier) === 0
                || strcasecmp((string) ($account['id'] ?? ''), $identifier) === 0
        );
    }

    private function mockExpertProfile(array $account): array
    {
        $idCard = (string) ($account['citizen_id'] ?? $account['username'] ?? $account['id']);
        $seed = ((int) ($account['id'] ?? 1)) % 10;
        $expertNames = [
            'Software Engineering',
            'Educational Technology',
            'Community Information System',
            'Smart Campus and IoT',
            'Artificial Intelligence',
            'Public Health Informatics',
            'Tourism Data Platform',
            'Smart Farming',
            'Digital Business',
            'Research Information System',
        ];

        return [
            'data' => [
                'profile' => [
                    'users_id' => (int) $account['id'],
                    'id_card' => $idCard,
                    'lrd_researcher_id' => $account['lrd_researcher_id'] ?? (1000 + $seed),
                    'expert_id' => $account['expert_id'] ?? (600 + $seed),
                    'prefix' => $account['prefix_th'] ?? null,
                    'th_firstname' => $account['first_name_th'] ?? '',
                    'th_lastname' => $account['last_name_th'] ?? '',
                    'en_firstname' => $account['first_name_en'] ?? '',
                    'en_lastname' => $account['last_name_en'] ?? '',
                    'email' => $account['email'] ?? null,
                    'tel' => $account['tel'] ?? '-',
                    'mobile' => $account['mobile'] ?? null,
                    'status_type' => $account['status_type'] ?? 'T',
                    'position_major' => $account['position_major'] ?? '',
                    'picture' => $account['picture_file'] ?? 'mock-profile.jpg',
                    'picture_url' => $account['picture'] ?? null,
                    'date_update' => $account['last_updated_at'] ?? now()->format('Y-m-d H:i:s'),
                    'sdname' => $account['department_name_th'] ?? $account['department_name_en'] ?? null,
                    'dname' => $account['faculty_name_th'] ?? $account['faculty_name_en'] ?? null,
                    'pname' => $account['position_name'] ?? 'Lecturer',
                    'unit_name' => $account['faculty_name_th'] ?? $account['faculty_name_en'] ?? null,
                    'status' => $account['status'] ?? 'ACTIVE',
                    'nationality' => $account['nationality'] ?? 'THAI',
                    'birth_date' => $account['birth_date'] ?? '1980-04-10',
                ],
                'expert' => [
                    'expert_id' => $account['expert_id'] ?? (600 + $seed),
                    'name' => $account['expert_name'] ?? ($expertNames[$seed] ?? $expertNames[0]),
                    'date_add' => $account['expert_date_add'] ?? '2024-01-15 09:00:00',
                ],
                'interests' => [],
                'education' => [],
                'workEx' => [],
                'boardex' => [],
                'lecturers' => [],
                'research' => [],
            ],
        ];
    }

    private function localExpertUserInfo(string $accessToken): array
    {
        $parts = explode('.', $accessToken, 3);
        if (count($parts) !== 3 || $parts[0] !== 'local-expert-token') {
            throw new \RuntimeException('Invalid local expert token.');
        }

        [, $usersId, $signature] = $parts;
        $expected = hash_hmac('sha256', $usersId, (string) config('app.key'));
        if (! hash_equals($expected, $signature)) {
            throw new \RuntimeException('Invalid local expert token signature.');
        }

        $user = DB::connection('expert')
            ->table('users')
            ->where('users_id', (int) $usersId)
            ->first();

        if (! $user) {
            throw new \RuntimeException('Local expert user not found.');
        }

        $department = $user->dep_id
            ? DB::connection('expert')->table('department')->where('dep_id', $user->dep_id)->first()
            : null;

        $subDepartment = $user->sub_dep_id
            ? DB::connection('expert')->table('sub_department')->where('sub_dep_id', $user->sub_dep_id)->first()
            : null;

        $researcher = DB::connection('lrd')
            ->table('researchers')
            ->where('idcard', $user->id_card)
            ->orWhere('codeuser', $user->id_card)
            ->orWhere('username', $user->username)
            ->first();

        return [
            'id' => (int) $user->users_id,
            'code' => (string) $user->users_id,
            'username' => $user->username,
            'citizen_id' => $user->id_card,
            'lrd_researcher_id' => $researcher ? $researcher->id : null,
            'type' => 'TEACHER',
            'degree' => null,
            'prefix_en' => null,
            'first_name_en' => $user->en_firstname ?: '',
            'last_name_en' => $user->en_lastname ?: '',
            'prefix_th' => $user->prefix,
            'first_name_th' => $user->th_firstname,
            'last_name_th' => $user->th_lastname,
            'gender' => $user->gender,
            'faculty_id' => $user->dep_id,
            'faculty_name_en' => $department ? $department->name_en : null,
            'faculty_name_th' => $department ? $department->name : null,
            'department_id' => $user->sub_dep_id,
            'department_name_en' => null,
            'department_name_th' => $subDepartment ? $subDepartment->name : null,
            'study_year' => 0,
            'nickname' => '',
            'email' => $user->email,
            'status' => $user->status ?: 'ACTIVE',
            'nationality' => 'THAI',
            'picture' => $user->picture,
            'picture_base64' => '',
            'birth_date' => $user->birthdate,
            'last_updated_at' => $user->date_update ?: $user->dateAdd,
            'teacher' => [],
            'study' => [],
            'custom1' => null,
            'custom2' => null,
            'custom3' => null,
            'campus_id' => null,
            'curriculum_id' => null,
            'passport_id' => '',
        ];
    }

    private function mockUserInfo(array $account): array
    {
        return [
            'id' => $account['id'],
            'code' => $account['code'],
            'type' => $account['type'] ?? 'TEACHER',
            'degree' => $account['degree'] ?? 'MASTER',
            'prefix_en' => $account['prefix_en'] ?? 'Mr.',
            'first_name_en' => $account['first_name_en'],
            'last_name_en' => $account['last_name_en'],
            'prefix_th' => $account['prefix_th'],
            'first_name_th' => $account['first_name_th'],
            'last_name_th' => $account['last_name_th'],
            'gender' => $account['gender'] ?? 'MALE',
            'faculty_id' => $account['faculty_id'] ?? 5,
            'faculty_name_en' => $account['faculty_name_en'] ?? 'Faculty of Industrial Technology',
            'faculty_name_th' => $account['faculty_name_th'] ?? 'คณะเทคโนโลยีอุตสาหกรรม',
            'department_id' => $account['department_id'] ?? 122,
            'department_name_en' => $account['department_name_en'] ?? 'Bachelor of Engineering Program',
            'department_name_th' => $account['department_name_th'] ?? 'หลักสูตรวิศวกรรมบัณฑิต',
            'study_year' => $account['study_year'] ?? 0,
            'nickname' => $account['nickname'] ?? '',
            'email' => $account['email'],
            'status' => $account['status'] ?? 'ACTIVE',
            'nationality' => $account['nationality'] ?? 'THAI',
            'picture' => $account['picture'] ?? null,
            'picture_base64' => '',
            'birth_date' => $account['birth_date'] ?? '1980-04-10',
            'last_updated_at' => $account['last_updated_at'] ?? now()->format('Y-m-d H:i:s'),
            'teacher' => [],
            'study' => [],
            'custom1' => $account['custom1'] ?? null,
            'custom2' => $account['custom2'] ?? null,
            'custom3' => $account['custom3'] ?? null,
            'campus_id' => $account['campus_id'] ?? 1,
            'curriculum_id' => $account['curriculum_id'] ?? 0,
            'username' => $account['username'],
            'citizen_id' => $account['citizen_id'] ?? null,
            'passport_id' => $account['passport_id'] ?? '',
        ];
    }
}
