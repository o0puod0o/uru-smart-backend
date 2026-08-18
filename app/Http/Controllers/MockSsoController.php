<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MockSsoController extends Controller
{
    public function token(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $account = collect(config('sso.mock_accounts', []))->first(
            fn (array $account): bool => $this->matchesLogin($account, $request->input('username'))
                && hash_equals((string) $account['password'], (string) $request->input('password'))
        );

        if (! $account) {
            return response()->json(['message' => 'Invalid mock SSO credentials'], 401);
        }

        $subject = (string) $account['id'];
        $signature = hash_hmac('sha256', $subject, (string) config('app.key'));

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'access_token' => "external-mock-sso-token.{$subject}.{$signature}",
        ]);
    }

    public function userinfo(Request $request)
    {
        $accessToken = trim(str_replace('Bearer ', '', (string) $request->header('Authorization')));
        $parts = explode('.', $accessToken, 3);

        if (count($parts) !== 3 || $parts[0] !== 'external-mock-sso-token') {
            return response()->json(['message' => 'Invalid mock SSO token'], 401);
        }

        [, $subject, $signature] = $parts;
        $expected = hash_hmac('sha256', $subject, (string) config('app.key'));

        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid mock SSO token signature'], 401);
        }

        $account = collect(config('sso.mock_accounts', []))->first(
            fn (array $account): bool => (string) $account['id'] === (string) $subject
        );

        if (! $account) {
            return response()->json(['message' => 'Mock SSO account not found'], 404);
        }

        return response()->json($this->formatUserInfo($account));
    }

    public function expert(Request $request, string $identifier)
    {
        $expectedToken = (string) config('sso.expert_api_token');

        if ($expectedToken === '' || ! hash_equals($expectedToken, (string) $request->query('token'))) {
            return response()->json(['message' => 'Invalid mock expert API token'], 401);
        }

        $account = collect(config('sso.mock_accounts', []))->first(
            fn (array $account): bool => $this->matchesExpertIdentifier($account, $identifier)
        );

        if (! $account) {
            return response()->json(['message' => 'Mock expert profile not found'], 404);
        }

        return response()->json($this->formatExpertProfile($account));
    }

    private function matchesLogin(array $account, string $login): bool
    {
        return strcasecmp((string) ($account['email'] ?? ''), $login) === 0
            || strcasecmp((string) ($account['username'] ?? ''), $login) === 0
            || strcasecmp((string) ($account['citizen_id'] ?? ''), $login) === 0;
    }

    private function matchesExpertIdentifier(array $account, string $identifier): bool
    {
        return strcasecmp((string) ($account['citizen_id'] ?? ''), $identifier) === 0
            || strcasecmp((string) ($account['username'] ?? ''), $identifier) === 0
            || strcasecmp((string) ($account['id'] ?? ''), $identifier) === 0;
    }

    private function formatUserInfo(array $account): array
    {
        return [
            'id' => $account['id'],
            'code' => $account['code'],
            'username' => $account['username'],
            'citizen_id' => $account['citizen_id'] ?? null,
            'type' => $account['type'] ?? 'TEACHER',
            'degree' => $account['degree'] ?? 'MASTER',
            'prefix_en' => $account['prefix_en'] ?? 'Mr.',
            'first_name_en' => $account['first_name_en'] ?? '',
            'last_name_en' => $account['last_name_en'] ?? '',
            'prefix_th' => $account['prefix_th'] ?? '',
            'first_name_th' => $account['first_name_th'] ?? '',
            'last_name_th' => $account['last_name_th'] ?? '',
            'gender' => $account['gender'] ?? 'MALE',
            'faculty_id' => $account['faculty_id'] ?? null,
            'faculty_name_en' => $account['faculty_name_en'] ?? null,
            'faculty_name_th' => $account['faculty_name_th'] ?? null,
            'department_id' => $account['department_id'] ?? null,
            'department_name_en' => $account['department_name_en'] ?? null,
            'department_name_th' => $account['department_name_th'] ?? null,
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
            'campus_id' => $account['campus_id'] ?? null,
            'curriculum_id' => $account['curriculum_id'] ?? null,
            'passport_id' => $account['passport_id'] ?? '',
        ];
    }

    private function formatExpertProfile(array $account): array
    {
        $idCard = (string) ($account['citizen_id'] ?? $account['username'] ?? $account['id']);
        $usersId = (int) $account['id'];
        $seed = $this->profileSeed($account);

        return [
            'data' => [
                'profile' => [
                    'users_id' => $usersId,
                    'id_card' => $idCard,
                    'expert_id' => $account['expert_id'] ?? (600 + $seed),
                    'prefix' => $account['prefix_th'] ?? 'Mr.',
                    'th_firstname' => $account['first_name_th'] ?? '',
                    'th_lastname' => $account['last_name_th'] ?? '',
                    'en_firstname' => $account['first_name_en'] ?? '',
                    'en_lastname' => $account['last_name_en'] ?? '',
                    'email' => $account['email'] ?? null,
                    'tel' => $account['tel'] ?? '-',
                    'mobile' => $account['mobile'] ?? ('08000000'.str_pad((string) $seed, 2, '0', STR_PAD_LEFT)),
                    'status_type' => $account['status_type'] ?? 'T',
                    'position_major' => $account['position_major'] ?? '',
                    'picture' => $account['picture_file'] ?? 'mock-profile.jpg',
                    'date_update' => $account['last_updated_at'] ?? now()->format('Y-m-d H:i:s'),
                    'sdname' => $account['department_name_th'] ?? $account['department_name_en'] ?? null,
                    'dname' => $account['faculty_name_th'] ?? $account['faculty_name_en'] ?? null,
                    'pname' => $account['position_name'] ?? 'Lecturer',
                    'unit_name' => $account['faculty_name_th'] ?? $account['faculty_name_en'] ?? null,
                    'picture_url' => $account['picture'] ?? null,
                ],
                'expert' => [
                    'expert_id' => $account['expert_id'] ?? (600 + $seed),
                    'name' => $account['expert_name'] ?? $this->mockExpertName($seed),
                    'date_add' => $account['expert_date_add'] ?? '2024-01-15 09:00:00',
                ],
                'interests' => $this->mockInterests($idCard, $seed),
                'education' => $this->mockEducation($idCard, $seed),
                'workEx' => $this->mockWorkExperience($idCard, $account, $seed),
                'boardex' => [],
                'lecturers' => [],
                'research' => $this->mockResearch($idCard, $seed),
            ],
        ];
    }

    private function profileSeed(array $account): int
    {
        return ((int) ($account['id'] ?? 1)) % 10;
    }

    private function mockExpertName(int $seed): string
    {
        $names = [
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

        return $names[$seed] ?? $names[0];
    }

    private function mockInterests(string $idCard, int $seed): array
    {
        $topics = [
            ['Mobile Application Development', 'Digital Service Design'],
            ['Learning Innovation', 'Educational Technology'],
            ['Community Data Platform', 'Local Wisdom Database'],
            ['IoT for Smart Campus', 'Sensor Data Analytics'],
            ['Artificial Intelligence', 'Machine Learning'],
            ['Public Health Informatics', 'Health Data Visualization'],
            ['Tourism Information System', 'Creative Economy'],
            ['Agricultural Technology', 'Smart Farming'],
            ['E-Commerce Platform', 'User Experience Design'],
            ['Research Management System', 'Open Data'],
        ];

        return collect($topics[$seed] ?? $topics[0])
            ->map(fn (string $name, int $index): array => [
                'id' => 7000 + ($seed * 10) + $index,
                'id_card' => $idCard,
                'name' => $name,
                'dateAdd' => '2024-01-15 09:00:00',
            ])
            ->all();
    }

    private function mockEducation(string $idCard, int $seed): array
    {
        $courses = [
            'M.Eng. Computer Engineering',
            'Ph.D. Educational Technology',
            'M.Sc. Information Technology',
            'M.B.A. Innovation Management',
            'Ph.D. Computer Science',
            'M.P.H. Public Health Informatics',
            'M.A. Tourism Management',
            'M.Sc. Agricultural Technology',
            'M.Sc. Digital Business',
            'Ph.D. Information Systems',
        ];

        return [
            [
                'id' => 8000 + $seed,
                'id_card' => $idCard,
                'degree' => $seed % 3 === 0 ? 4 : 3,
                'course' => $courses[$seed] ?? $courses[0],
                'university' => 'Uttaradit Rajabhat University',
                'year' => (string) (2550 + $seed),
                'dateAdd' => '2024-01-15 09:05:00',
                'name' => $seed % 3 === 0 ? 'Doctoral Degree' : 'Master Degree',
                'date_add' => '2024-01-15',
            ],
        ];
    }

    private function mockWorkExperience(string $idCard, array $account, int $seed): array
    {
        return [
            [
                'id' => 9000 + $seed,
                'id_card' => $idCard,
                'year_start' => (string) (2555 + $seed),
                'year_end' => 'Present',
                'position' => $account['position_name'] ?? 'Lecturer',
                'workplace' => $account['faculty_name_en'] ?? $account['faculty_name_th'] ?? 'Uttaradit Rajabhat University',
                'dateAdd' => '2024-01-15 09:10:00',
            ],
        ];
    }

    private function mockResearch(string $idCard, int $seed): array
    {
        $names = [
            'Mobile Application for University Student Services',
            'Digital Learning Platform for Local Schools',
            'Community Data Center for Local Development',
            'Smart Campus Monitoring with IoT Devices',
            'AI-Based Recommendation for Academic Support',
            'Health Information Dashboard for Community Hospitals',
            'Tourism Data Platform for Uttaradit Province',
            'Smart Farming Prototype for Local Farmers',
            'E-Commerce Prototype for Community Products',
            'Research Management API for University Mobile App',
        ];

        return [
            [
                'id' => 10000 + $seed,
                'id_card' => $idCard,
                'name' => $names[$seed] ?? $names[0],
                'year' => (string) (2560 + $seed),
                'research_type_id' => 1,
                'research_PMU_type_id' => 0,
                'research_level_id' => 0,
                'dateAdd' => '2024-01-15 09:15:00',
                'rtn' => 'Internal Research Fund',
                'pname' => null,
                'lname' => null,
            ],
        ];
    }
}
