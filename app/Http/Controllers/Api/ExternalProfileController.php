<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExternalProfileController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();
        $idCard = $user->citizen_id;

        $expertProfile = DB::connection('expert')
            ->table('users')
            ->leftJoin('department', 'users.dep_id', '=', 'department.dep_id')
            ->leftJoin('sub_department', 'users.sub_dep_id', '=', 'sub_department.sub_dep_id')
            ->select([
                'users.users_id',
                'users.username',
                'users.id_card',
                'users.prefix',
                'users.th_firstname',
                'users.th_lastname',
                'users.en_firstname',
                'users.en_lastname',
                'users.email',
                'users.tel',
                'users.mobile',
                'users.picture',
                'users.date_update',
                'department.name as department_name',
                'sub_department.name as sub_department_name',
            ])
            ->where('users.id_card', $idCard)
            ->first();

        $researcher = $this->findLrdResearcher($user);
        $researcherId = $researcher ? $researcher->id : null;

        return response()->json([
            'data' => [
                'token_user' => [
                    'id' => $user->id,
                    'sso_id' => $user->sso_id,
                    'citizen_id' => $user->citizen_id,
                    'lrd_researcher_id' => $user->lrd_researcher_id,
                ],
                'expert2' => [
                    'profile' => $expertProfile,
                    'education' => $this->expertRows('education', $idCard),
                    'interests' => $this->expertRows('has_interest', $idCard),
                    'research' => $this->expertRows('has_research', $idCard),
                    'journals' => $this->expertRows('has_journal', $idCard),
                    'workex' => $this->expertRows('workex', $idCard),
                ],
                'lrdsystem2' => [
                    'researcher' => $researcher,
                    'projects' => $this->lrdRows('projects', 'researcher_id', $researcherId),
                    'proposals' => $this->lrdRows('proposals', 'researcher_id', $researcherId),
                    'researchs' => $this->lrdRows('researchs', 'researcher_id', $researcherId),
                ],
            ],
        ]);
    }

    private function expertRows(string $table, ?string $idCard)
    {
        if (! $idCard) {
            return collect();
        }

        return DB::connection('expert')
            ->table($table)
            ->where('id_card', $idCard)
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    private function lrdRows(string $table, string $key, ?int $researcherId)
    {
        if (! $researcherId) {
            return collect();
        }

        return DB::connection('lrd')
            ->table($table)
            ->where($key, $researcherId)
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    private function findLrdResearcher($user)
    {
        if ($user->lrd_researcher_id) {
            $researcher = DB::connection('lrd')
                ->table('researchers')
                ->where('id', $user->lrd_researcher_id)
                ->first();

            if ($researcher) {
                return $researcher;
            }
        }

        return DB::connection('lrd')
            ->table('researchers')
            ->where('idcard', $user->citizen_id)
            ->orWhere('codeuser', $user->citizen_id)
            ->orWhere('username', $user->username)
            ->first();
    }
}
