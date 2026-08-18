<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SSOService;
use App\Services\SSOUserSynchronizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        protected SSOService $sso,
        protected SSOUserSynchronizer $users
    )
    {
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ]);

        try {
            $tokenData = $this->sso->loginWithPassword(
                $request->email,
                $request->password
            );

            $ssoUser = $this->sso->getUserInfo($tokenData['access_token']);
            $teacher = $this->users->sync($ssoUser);

            return $this->issueMobileToken($teacher);
        } catch (\Throwable $e) {
            Log::error('Login failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Invalid email or password',
            ], 401);
        }
    }

    public function loginWithSsoToken(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            $ssoUser = $this->sso->getUserInfo($request->input('access_token'));
            $teacher = $this->users->sync($ssoUser);

            return $this->issueMobileToken($teacher);
        } catch (\Throwable $e) {
            Log::error('SSO token login failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Invalid SSO token',
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    private function issueMobileToken(User $teacher)
    {
        $sanctumToken = $teacher->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $sanctumToken,
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
        ]);
    }
}
