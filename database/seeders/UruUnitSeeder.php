<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UruUnitSeeder extends Seeder
{
    public function run(): void
    {
        $mainUnits = $this->uniqueNames(config('uru_units.main_units', []));
        $subUnits = $this->uniqueNames(config('uru_units.sub_units', []));

        if (empty($mainUnits)) {
            return;
        }

        $departmentIds = $this->seedDepartments($mainUnits);
        $fallbackDepartmentId = $departmentIds['สำนักงานอธิการบดี']
            ?? $departmentIds[array_key_first($departmentIds)];

        $this->seedSubDepartments($subUnits, $departmentIds, $fallbackDepartmentId);
    }

    private function seedDepartments(array $mainUnits): array
    {
        $existing = DB::table('departments')
            ->pluck('dep_id', 'name')
            ->all();

        $nextId = ((int) DB::table('departments')->max('dep_id')) + 1;

        foreach ($mainUnits as $index => $name) {
            if (isset($existing[$name])) {
                continue;
            }

            DB::table('departments')->insert([
                'dep_id' => $nextId,
                'name' => $name,
                'sort' => $index + 1,
                'name_en' => null,
            ]);

            $existing[$name] = $nextId;
            $nextId++;
        }

        return array_map('intval', $existing);
    }

    private function seedSubDepartments(array $subUnits, array $departmentIds, int $fallbackDepartmentId): void
    {
        $existing = DB::table('sub_departments')
            ->pluck('sub_dep_id', 'name')
            ->all();

        $currentDepartmentId = $fallbackDepartmentId;

        foreach ($subUnits as $name) {
            $currentDepartmentId = $this->guessDepartmentId($name, $departmentIds)
                ?? $currentDepartmentId
                ?? $fallbackDepartmentId;

            if (isset($existing[$name])) {
                continue;
            }

            DB::table('sub_departments')->insert([
                'dep_id' => $currentDepartmentId,
                'name' => $name,
                'name_en' => null,
            ]);
        }
    }

    private function guessDepartmentId(string $subUnitName, array $departmentIds): ?int
    {
        if (isset($departmentIds[$subUnitName])) {
            return $departmentIds[$subUnitName];
        }

        $normalizedName = $this->normalizeUnitName($subUnitName);

        foreach ($departmentIds as $departmentName => $departmentId) {
            if ($normalizedName === $this->normalizeUnitName($departmentName)) {
                return (int) $departmentId;
            }
        }

        return null;
    }

    private function normalizeUnitName(string $name): string
    {
        return trim(preg_replace('/^สำนักงาน/u', '', $name));
    }

    private function uniqueNames(array $names): array
    {
        return collect($names)
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique(fn (string $name): string => mb_strtolower($name, 'UTF-8'))
            ->values()
            ->all();
    }
}
