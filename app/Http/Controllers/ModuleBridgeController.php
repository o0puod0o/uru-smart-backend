<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ModuleBridgeController extends Controller
{
    public function session(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'authenticated' => true,
            'user' => $this->userPayload($user),
            'modules' => [
                'research' => [
                    'name' => 'LRD Research Module',
                    'api_prefix' => '/api/lrd',
                    'permissions' => [
                        'read_all' => true,
                        'write_own' => true,
                    ],
                ],
                'expert' => [
                    'name' => 'Expert/Profile Module',
                    'api_prefix' => '/api',
                    'permissions' => [
                        'read_profile' => true,
                        'update_own_profile' => true,
                    ],
                ],
            ],
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'code' => $user->code,
            'citizen_id' => $user->citizen_id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name_th' => $user->full_name_th,
            'full_name_en' => $user->full_name_en,
            'faculty_name_th' => $user->faculty_name_th,
            'department_name_th' => $user->department_name_th,
            'picture' => $user->display_picture,
            'lrd_researcher_id' => $user->lrd_researcher_id,
        ];
    }
}
