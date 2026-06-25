<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SSOService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(protected SSOService $sso)
    {
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $tokenData = $this->sso->loginWithPassword(
                $request->email,
                $request->password
            );

            $ssoUser = $this->sso->getUserInfo($tokenData['access_token']);
            $teacher = $this->upsertUser($ssoUser);

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
            $teacher = $this->upsertUser($ssoUser);

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

    private function upsertUser(array $ssoUser): User
    {
        return User::updateOrCreate(
            ['sso_id' => $ssoUser['id']],
            [
                'code' => $ssoUser['code'],
                'username' => $ssoUser['username'],
                'citizen_id' => $ssoUser['citizen_id'] ?? null,
                'passport_id' => $ssoUser['passport_id'] ?? null,
                'type' => $ssoUser['type'],
                'degree' => $ssoUser['degree'],
                'status' => $ssoUser['status'],
                'prefix_th' => $ssoUser['prefix_th'],
                'first_name_th' => trim($ssoUser['first_name_th']),
                'last_name_th' => $ssoUser['last_name_th'],
                'prefix_en' => $ssoUser['prefix_en'],
                'first_name_en' => trim($ssoUser['first_name_en']),
                'last_name_en' => $ssoUser['last_name_en'],
                'nickname' => $ssoUser['nickname'] ?? null,
                'gender' => $ssoUser['gender'],
                'birth_date' => $ssoUser['birth_date'],
                'nationality' => $ssoUser['nationality'],
                'email' => $ssoUser['email'],
                'sso_picture' => $ssoUser['picture'] ?? null,
                'faculty_id' => $ssoUser['faculty_id'],
                'faculty_name_th' => $ssoUser['faculty_name_th'],
                'faculty_name_en' => $ssoUser['faculty_name_en'],
                'department_id' => $ssoUser['department_id'],
                'department_name_th' => $ssoUser['department_name_th'],
                'department_name_en' => $ssoUser['department_name_en'],
                'campus_id' => $ssoUser['campus_id'] ?? null,
                'curriculum_id' => $ssoUser['curriculum_id'] ?? null,
                'study_year' => $ssoUser['study_year'] ?? 0,
                'custom1' => $ssoUser['custom1'] ?? null,
                'custom2' => $ssoUser['custom2'] ?? null,
                'custom3' => $ssoUser['custom3'] ?? null,
                'sso_last_updated_at' => $ssoUser['last_updated_at'],
            ]
        );
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
