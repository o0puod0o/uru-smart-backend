<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SSOUserSynchronizer
{
    public function sync(array $ssoUser): User
    {
        $ssoUser = $this->normalize($ssoUser);

        $user = $this->findExistingUser($ssoUser) ?? new User();
        $ssoId = $this->availableSsoId($ssoUser['id'] ?? null, $user);

        $user->fill($this->onlyExistingUserColumns([
            'sso_id' => $ssoId,
            'code' => $ssoUser['code'],
            'username' => $ssoUser['username'],
            'citizen_id' => $ssoUser['citizen_id'] ?? null,
            'lrd_researcher_id' => $ssoUser['lrd_researcher_id'] ?? null,
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
        ], $user));

        if (Schema::hasColumn($user->getTable(), 'name')) {
            $user->name = trim(($ssoUser['first_name_th'] ?? '') . ' ' . ($ssoUser['last_name_th'] ?? ''))
                ?: trim(($ssoUser['first_name_en'] ?? '') . ' ' . ($ssoUser['last_name_en'] ?? ''))
                ?: ($ssoUser['email'] ?? $ssoUser['username'] ?? 'SSO User');
        }

        if (Schema::hasColumn($user->getTable(), 'password') && ! $user->exists && empty($user->password)) {
            $user->password = Hash::make(Str::random(40));
        }

        $user->save();

        return $user;
    }

    private function normalize(array $payload): array
    {
        if (isset($payload['data']['profile']) && is_array($payload['data']['profile'])) {
            return $this->normalizeExpertCallback($payload);
        }

        return $this->normalizeFlatPayload($payload);
    }

    private function findExistingUser(array $ssoUser): ?User
    {
        $citizenId = $this->stringOrNull($ssoUser['citizen_id'] ?? null);
        $username = $this->stringOrNull($ssoUser['username'] ?? null);
        $email = $this->stringOrNull($ssoUser['email'] ?? null);
        $ssoId = $ssoUser['id'] ?? null;

        if ($citizenId) {
            $user = User::query()
                ->where('citizen_id', $citizenId)
                ->orWhere('username', $citizenId)
                ->first();

            if ($user) {
                return $user;
            }
        }

        if ($username) {
            $user = User::query()->where('username', $username)->first();

            if ($user) {
                return $user;
            }
        }

        if ($ssoId) {
            $user = User::query()->where('sso_id', $ssoId)->first();

            if ($user) {
                return $user;
            }
        }

        return $email ? User::query()->where('email', $email)->first() : null;
    }

    private function onlyExistingUserColumns(array $attributes, User $user): array
    {
        $table = $user->getTable();

        return array_filter(
            $attributes,
            fn (string $column): bool => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function availableSsoId(mixed $ssoId, User $currentUser): mixed
    {
        if (! $ssoId) {
            return $currentUser->sso_id;
        }

        $owner = User::query()->where('sso_id', $ssoId)->first();

        if ($owner && $currentUser->exists && $owner->id !== $currentUser->id) {
            return $currentUser->sso_id;
        }

        if ($owner && ! $currentUser->exists) {
            return null;
        }

        return $ssoId;
    }

    private function normalizeExpertCallback(array $payload): array
    {
        $profile = $payload['data']['profile'];
        $expert = $payload['data']['expert'] ?? [];
        $idCard = $this->stringOrNull($profile['id_card'] ?? null);
        $usersId = (int) ($profile['users_id'] ?? 0);
        $picture = $profile['picture_url'] ?? $profile['picture'] ?? null;
        $researcherId = $profile['lrd_researcher_id'] ?? null;

        return [
            'id' => $usersId,
            'code' => (string) ($profile['users_id'] ?? $idCard ?? ''),
            'username' => (string) ($profile['username'] ?? $idCard ?? $profile['email'] ?? $usersId),
            'citizen_id' => $idCard,
            'lrd_researcher_id' => $researcherId,
            'passport_id' => $this->stringOrNull($profile['passport_id'] ?? null),
            'type' => $this->stringOrDefault($profile['status_type'] ?? null, 'TEACHER'),
            'degree' => $expert['expert_id'] ?? null,
            'status' => $this->stringOrDefault($profile['status'] ?? null, 'ACTIVE'),
            'prefix_th' => $this->stringOrNull($profile['prefix'] ?? null),
            'first_name_th' => $this->stringOrDefault($profile['th_firstname'] ?? null, ''),
            'last_name_th' => $this->stringOrDefault($profile['th_lastname'] ?? null, ''),
            'prefix_en' => $this->stringOrNull($profile['prefix_en'] ?? null),
            'first_name_en' => $this->stringOrDefault($profile['en_firstname'] ?? null, ''),
            'last_name_en' => $this->stringOrDefault($profile['en_lastname'] ?? null, ''),
            'nickname' => $this->stringOrNull($profile['nickname'] ?? null),
            'gender' => $this->stringOrNull($profile['gender'] ?? null),
            'birth_date' => $this->stringOrNull($profile['birthdate'] ?? $profile['birth_date'] ?? null),
            'nationality' => $this->stringOrDefault($profile['nationality'] ?? null, 'THAI'),
            'email' => $this->stringOrNull($profile['email'] ?? null),
            'picture' => $this->stringOrNull($picture),
            'faculty_id' => $profile['dep_id'] ?? null,
            'faculty_name_th' => $this->stringOrNull($profile['dname'] ?? $profile['unit_name'] ?? null),
            'faculty_name_en' => $this->stringOrNull($profile['dname_en'] ?? null),
            'department_id' => $profile['sub_dep_id'] ?? null,
            'department_name_th' => $this->stringOrNull($profile['sdname'] ?? null),
            'department_name_en' => $this->stringOrNull($profile['sdname_en'] ?? null),
            'campus_id' => $profile['campus_id'] ?? null,
            'curriculum_id' => $profile['curriculum_id'] ?? null,
            'study_year' => $profile['study_year'] ?? 0,
            'custom1' => null,
            'custom2' => null,
            'custom3' => null,
            'last_updated_at' => $this->stringOrNull($profile['date_update'] ?? null) ?? now()->format('Y-m-d H:i:s'),
        ];
    }

    private function normalizeFlatPayload(array $payload): array
    {
        $idCard = $this->stringOrNull($payload['citizen_id'] ?? null);

        return [
            'id' => (int) ($payload['id'] ?? 0),
            'code' => (string) ($payload['code'] ?? $payload['id'] ?? $idCard ?? ''),
            'username' => (string) ($payload['username'] ?? $idCard ?? $payload['email'] ?? $payload['id'] ?? ''),
            'citizen_id' => $idCard,
            'lrd_researcher_id' => $payload['lrd_researcher_id'] ?? null,
            'passport_id' => $this->stringOrNull($payload['passport_id'] ?? null),
            'type' => $this->stringOrDefault($payload['type'] ?? null, 'TEACHER'),
            'degree' => $payload['degree'] ?? null,
            'status' => $this->stringOrDefault($payload['status'] ?? null, 'ACTIVE'),
            'prefix_th' => $this->stringOrNull($payload['prefix_th'] ?? null),
            'first_name_th' => $this->stringOrDefault($payload['first_name_th'] ?? null, ''),
            'last_name_th' => $this->stringOrDefault($payload['last_name_th'] ?? null, ''),
            'prefix_en' => $this->stringOrNull($payload['prefix_en'] ?? null),
            'first_name_en' => $this->stringOrDefault($payload['first_name_en'] ?? null, ''),
            'last_name_en' => $this->stringOrDefault($payload['last_name_en'] ?? null, ''),
            'nickname' => $this->stringOrNull($payload['nickname'] ?? null),
            'gender' => $this->stringOrNull($payload['gender'] ?? null),
            'birth_date' => $this->stringOrNull($payload['birth_date'] ?? null),
            'nationality' => $this->stringOrDefault($payload['nationality'] ?? null, 'THAI'),
            'email' => $this->stringOrNull($payload['email'] ?? null),
            'picture' => $this->stringOrNull($payload['picture'] ?? null),
            'faculty_id' => $payload['faculty_id'] ?? null,
            'faculty_name_th' => $this->stringOrNull($payload['faculty_name_th'] ?? null),
            'faculty_name_en' => $this->stringOrNull($payload['faculty_name_en'] ?? null),
            'department_id' => $payload['department_id'] ?? null,
            'department_name_th' => $this->stringOrNull($payload['department_name_th'] ?? null),
            'department_name_en' => $this->stringOrNull($payload['department_name_en'] ?? null),
            'campus_id' => $payload['campus_id'] ?? null,
            'curriculum_id' => $payload['curriculum_id'] ?? null,
            'study_year' => $payload['study_year'] ?? 0,
            'custom1' => $payload['custom1'] ?? null,
            'custom2' => $payload['custom2'] ?? null,
            'custom3' => $payload['custom3'] ?? null,
            'last_updated_at' => $this->stringOrNull($payload['last_updated_at'] ?? null) ?? now()->format('Y-m-d H:i:s'),
        ];
    }

    private function findLrdResearcherId(?string $idCard, ?string $username): ?int
    {
        if (! $idCard && ! $username) {
            return null;
        }

        try {
            $query = DB::connection('lrd')->table('researchers');

            if ($idCard) {
                $query->where('idcard', $idCard)
                    ->orWhere('codeuser', $idCard);
            }

            if ($username) {
                $query->orWhere('username', $username);
            }

            return $query->value('id');
        } catch (\Throwable) {
            return null;
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function stringOrDefault(mixed $value, string $default): string
    {
        return $this->stringOrNull($value) ?? $default;
    }
}
