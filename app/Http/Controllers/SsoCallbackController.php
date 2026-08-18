<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SSOService;
use App\Services\SSOUserSynchronizer;
use Illuminate\Http\Request;
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
        if ($request->filled('error')) {
            return response()->json([
                'message' => 'SSO callback returned an error.',
                'error' => $request->input('error'),
                'error_description' => $request->input('error_description'),
            ], 400);
        }

        try {
            $accessToken = $request->input('access_token');

            if (! $accessToken && $request->filled('code')) {
                $tokenData = $this->sso->exchangeAuthorizationCode(
                    (string) $request->input('code'),
                    (string) $request->input('redirect_uri', config('sso.redirect_uri'))
                );

                $accessToken = $tokenData['access_token'] ?? null;
            }

            if (! $accessToken) {
                return response()->json([
                    'message' => 'SSO callback requires code or access_token.',
                ], 422);
            }

            $ssoUser = $this->sso->getUserInfo((string) $accessToken);
            $teacher = $this->users->sync($ssoUser);
            $mobileToken = $teacher->createToken('mobile-app')->plainTextToken;
            $payload = $this->payload($teacher, $mobileToken);

            if ($request->expectsJson()) {
                return response()->json($payload);
            }

            return response()->view('auth.callback', $payload);
        } catch (\Throwable $e) {
            Log::error('SSO callback failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'SSO callback failed.',
            ], 401);
        }
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
