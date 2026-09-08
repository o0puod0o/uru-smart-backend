<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SSOService;
use App\Services\SSOUserSynchronizer;
use App\Support\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SsoCallbackController extends Controller
{
    public function __construct(
        private SSOService $sso,
        private SSOUserSynchronizer $users
    ) {
    }

    public function __invoke(Request $request)
    {
        $startedAt = microtime(true);
        $this->logTiming('start', $startedAt, [
            'has_code' => $request->filled('code'),
            'has_access_token' => $request->filled('access_token'),
            'state_type' => $this->isAdminCallback($request) ? 'admin' : 'mobile',
        ]);

        if ($request->filled('error')) {
            $payload = [
                'type' => 'SSO_ERROR',
                'message' => 'SSO callback returned an error.',
                'error' => $request->input('error'),
                'error_description' => $request->input('error_description'),
            ];

            if ($request->expectsJson()) {
                return response()->json($payload, 400);
            }

            return response()->view('auth.callback', ['payload' => $payload], 400);
        }

        try {
            $accessToken = $request->input('access_token');
            $tokenData = [];

            if (! $accessToken && $request->filled('code')) {
                $this->logTiming('exchange_code_start', $startedAt);

                $tokenData = $this->sso->exchangeAuthorizationCode(
                    (string) $request->input('code'),
                    (string) $request->input('redirect_uri', config('sso.redirect_uri'))
                );

                $accessToken = $tokenData['access_token'] ?? null;
                $this->logTiming('exchange_code_done', $startedAt, [
                    'has_access_token' => (bool) $accessToken,
                    'has_user_in_token_data' => (bool) $this->extractUserFromTokenData($tokenData),
                ]);
            }

            $ssoUser = $this->extractUserFromTokenData($tokenData);

            if (! $accessToken && ! $ssoUser) {
                $payload = [
                    'type' => 'SSO_ERROR',
                    'message' => 'SSO callback requires code or access_token.',
                ];

                if ($request->expectsJson()) {
                    return response()->json($payload, 422);
                }

                return response()->view('auth.callback', ['payload' => $payload], 422);
            }

            if (! $ssoUser) {
                $this->logTiming('userinfo_start', $startedAt);
                $ssoUser = $this->sso->getUserInfo((string) $accessToken);
                $this->logTiming('userinfo_done', $startedAt);
            }

            $this->logTiming('sync_user_start', $startedAt);
            $teacher = $this->users->sync($ssoUser);
            $this->logTiming('sync_user_done', $startedAt, ['user_id' => $teacher->id]);

            if ($this->isAdminCallback($request)) {
                return $this->handleAdminCallback($request, $teacher);
            }

            $mobileToken = $teacher->createToken('mobile-app')->plainTextToken;
            $payload = $this->payload($teacher, $mobileToken);
            $payload['type'] = 'SSO_SUCCESS';
            $this->logTiming('mobile_token_created', $startedAt, ['user_id' => $teacher->id]);

            if ($request->expectsJson() && $request->query('response') === 'json') {
                $this->logTiming('return_json_response', $startedAt, ['user_id' => $teacher->id]);
                return response()->json($payload);
            }

            $this->logTiming('return_postmessage_response', $startedAt, ['user_id' => $teacher->id]);
            return response()->view('auth.callback', ['payload' => $payload] + $payload);
        } catch (\Throwable $e) {
            $this->logTiming('failed', $startedAt, [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            Log::error('SSO callback failed', ['error' => $e->getMessage()]);

            $response = [
                'type' => 'SSO_ERROR',
                'message' => 'SSO callback failed.',
            ];

            if ($request->boolean('debug')) {
                $response['error'] = $e->getMessage();
            }

            if (! $request->expectsJson() && ! $this->isAdminCallback($request)) {
                return response()->view('auth.callback', ['payload' => $response], 401);
            }

            return response()->json($response, 401);
        }
    }

    private function isAdminCallback(Request $request): bool
    {
        return str_starts_with((string) $request->input('state'), 'admin:');
    }

    private function handleAdminCallback(Request $request, User $user)
    {
        $state = (string) $request->input('state');
        $expectedState = (string) $request->session()->pull('admin_sso_state', '');

        if ($expectedState === '' || ! hash_equals('admin:'.$expectedState, $state)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'SSO admin state ไม่ถูกต้อง กรุณาลองเข้าสู่ระบบใหม่']);
        }

        if (! AdminAccess::allows($user)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'บัญชีนี้ไม่มีสิทธิ์เข้าใช้งานระบบจัดการ']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    private function logTiming(string $event, float $startedAt, array $context = []): void
    {
        $payload = array_merge([
            'event' => $event,
            'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'time' => now()->format('Y-m-d H:i:s'),
        ], $context);

        Log::info('SSO callback timing', $payload);

        try {
            $safePayload = $this->sanitizeLogPayload($payload);
            file_put_contents(
                storage_path('logs/sso-callback-timing.log'),
                json_encode($safePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        } catch (\Throwable) {
            // Do not break login if the diagnostic log file cannot be written.
        }
    }

    private function sanitizeLogPayload(array $payload): array
    {
        foreach (['token', 'access_token', 'code', 'email', 'citizen_id'] as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = '[REDACTED]';
            }
        }

        if (isset($payload['message']) && is_string($payload['message'])) {
            $payload['message'] = preg_replace('/code=[A-Za-z0-9._-]+/', 'code=[REDACTED]', $payload['message']);
            $payload['message'] = preg_replace('/Bearer\s+[A-Za-z0-9|._-]+/', 'Bearer [REDACTED]', $payload['message']);
        }

        return $payload;
    }

    private function extractUserFromTokenData(array $tokenData): ?array
    {
        foreach (['user', 'profile', 'account'] as $key) {
            if (isset($tokenData[$key]) && is_array($tokenData[$key])) {
                return $tokenData[$key];
            }
        }

        if (isset($tokenData['data']) && is_array($tokenData['data'])) {
            if (isset($tokenData['data']['profile']) && is_array($tokenData['data']['profile'])) {
                return $tokenData;
            }

            return $tokenData['data'];
        }

        return null;
    }

    private function payload(User $teacher, string $mobileToken): array
    {
        return [
            'token' => $mobileToken,
            'user' => [
                'id' => $teacher->id,
                'code' => $teacher->code,
                'full_name_th' => $teacher->full_name_th,
                'full_name_en' => $teacher->full_name_en,
                'email' => $teacher->email,
                'faculty_name_th' => $teacher->faculty_name_th,
                'department_name_th' => $teacher->department_name_th,
                'picture' => $teacher->display_picture,
            ],
        ];
    }
}
