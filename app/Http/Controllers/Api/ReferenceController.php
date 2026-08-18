<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResearchType;
use App\Models\ResearchLevel;
use App\Models\ResearchPmuType;
use App\Models\JournalType;
use App\Models\Degree;
use App\Models\Department;
use App\Models\Position;
use App\Models\SubDepartment;
use App\Models\HasExpert;
use Illuminate\Support\Facades\File;

/**
 * ReferenceController
 * ดึงข้อมูล reference/dropdown สำหรับ frontend
 * GET /api/ref/* : protected routes (ต้อง auth:sanctum)
 * Includes: prefixes, positions, lines, main_units, sub_units, expertise_groups, etc.
 */
class ReferenceController extends Controller
{
    /**
     * GET /api/ref/profile-options
     * ดึงข้อมูล dropdown ทั้งหมดสำหรับ profile form
     * Response: { prefixes, positions (86 รายการ), lines (2), main_units, sub_units }
     */
    public function profileOptions()
    {
        return response()->json([
            'prefixes' => $this->prefixItems(),
            'positions' => $this->positionItems(),
            'lines' => $this->lineItems(),
            'main_units' => $this->mainUnitItems(),
            'sub_units' => $this->subUnitItems(),
        ]);
    }

    public function searchOptions()
    {
        return response()->json([
            'search_from' => $this->searchFieldItems(),
            'expertise_groups' => $this->uniqueOptionItems('กลุ่มความเชี่ยวชาญทั้งหมด'),
            'interests' => $this->uniqueOptionItems('ความสนใจทั้งหมด'),
        ]);
    }

    public function searchFields()
    {
        return response()->json($this->searchFieldItems());
    }

    public function expertiseOptions()
    {
        return response()->json($this->uniqueOptionItems('กลุ่มความเชี่ยวชาญทั้งหมด'));
    }

    public function interestOptions()
    {
        return response()->json($this->uniqueOptionItems('ความสนใจทั้งหมด'));
    }

    public function prefixes()
    {
        return response()->json($this->prefixItems());
    }

    public function positions()
    {
        return response()->json($this->positionItems());
    }

    public function lines()
    {
        return response()->json($this->lineItems());
    }

    public function mainUnits()
    {
        return response()->json($this->mainUnitItems());
    }

    public function subUnits()
    {
        return response()->json($this->subUnitItems());
    }

    // ===== Private Helper Methods =====
    // ใช้ภายใน profileOptions() และ reference endpoints

    /**
     * ดึง prefix options (คำนำหน้า)
     * hardcoded 7 ตัว: นาย, นาง, นางสาว, ดร., ผศ., รศ., ศ.
     */
    private function prefixItems(): array
    {
        return [
            ['id' => 'นาย', 'name' => 'นาย'],
            ['id' => 'นาง', 'name' => 'นาง'],
            ['id' => 'นางสาว', 'name' => 'นางสาว'],
            ['id' => 'ดร.', 'name' => 'ดร.'],
            ['id' => 'ผศ.', 'name' => 'ผศ.'],
            ['id' => 'รศ.', 'name' => 'รศ.'],
            ['id' => 'ศ.', 'name' => 'ศ.'],
        ];
    }

    /**
     * ดึง positions จาก database (86 รายการ)
     * Includes: position_type_name mapping (สายวิชาการ/สายสนับสนุน)
     * Ordered by: id (เพื่อให้เรียงตามลำดับ)
     */
    private function positionItems()
    {
        return Position::with('positionType:id,name')
            ->orderBy('id')
            ->get(['id', 'name', 'position_type_id'])
            ->map(fn (Position $position): array => [
                'id' => $position->id,
                'name' => $position->name,
                'position_type_id' => $position->position_type_id,
                'position_type_name' => $position->positionType ? $position->positionType->name : null,
            ])
            ->values();
    }

    /**
     * ดึง lines (สาย) - 2 ตัว
     * สายวิชาการ, สายสนับสนุน
     * ใช้ใน position_type mapping
     */
    private function lineItems(): array
    {
        return [
            ['id' => 'สายวิชาการ', 'name' => 'สายวิชาการ'],
            ['id' => 'สายสนับสนุน', 'name' => 'สายสนับสนุน'],
        ];
    }

    /**
     * ดึง main units (คณะ/สำนัก) - 15 หน่วยงาน
     * Ordered by: name (ชื่อไทย)
     */
    private function mainUnitItems()
    {
        $query = Department::orderBy('name');

        if ($names = config('uru_units.main_units', [])) {
            $query->whereIn('name', $names);
        }

        return $query->get(['dep_id as id', 'name', 'name_en']);
    }

    /**
     * ดึง sub units (ภาค/ฝ่าย) ที่สังกัด main_unit
     * Optional filter: ?main_unit_id=1 (ดึงเฉพาะ sub units ของ main unit ที่ระบุ)
     * Ordered by: name (ชื่อไทย)
     */
    private function subUnitItems()
    {
        $query = SubDepartment::orderBy('name');

        if ($names = config('uru_units.sub_units', [])) {
            $query->whereIn('name', $names);
        }

        if (request()->has('main_unit_id')) {
            $query->where('dep_id', request('main_unit_id'));
        }

        return $query->get(['sub_dep_id as id', 'dep_id', 'name', 'name_en']);
    }

    private function searchFieldItems(): array
    {
        return [
            ['id' => 'first_name', 'name' => 'ชื่อ'],
            ['id' => 'last_name', 'name' => 'นามสกุล'],
            ['id' => 'expertise_group', 'name' => 'กลุ่มความเชี่ยวชาญ'],
            ['id' => 'interest', 'name' => 'ความสนใจ'],
            ['id' => 'research', 'name' => 'ผลงานวิจัย'],
            ['id' => 'proceeding', 'name' => 'Proceeding'],
            ['id' => 'journal', 'name' => 'Journal'],
        ];
    }

    public function researchTypes()
    {
        return response()->json(ResearchType::orderBy('orders')->get());
    }

    public function researchLevels()
    {
        return response()->json(ResearchLevel::all());
    }

    public function researchPmuTypes()
    {
        return response()->json(ResearchPmuType::all());
    }

    public function journalTypes()
    {
        return response()->json(JournalType::orderBy('orders')->get());
    }

    public function degrees()
    {
        return response()->json(Degree::all());
    }

    public function departments()
    {
        return response()->json(Department::all());
    }

    public function expertiseGroups()
    {
        return response()->json(HasExpert::orderBy('name')->get(['expert_id as id', 'name']));
    }

    private function uniqueOptionItems(?string $allLabel = null): array
    {
        $path = database_path('data/unique_options.txt');

        if (! File::exists($path)) {
            return $allLabel ? [['id' => 'all', 'name' => $allLabel]] : [];
        }

        $items = collect(File::lines($path))
            ->map(fn (string $line): string => html_entity_decode(trim($line), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->map(fn (string $line): string => trim(ltrim($line, "- \t\n\r\0\x0B")))
            ->filter(fn (string $line): bool => $line !== '' && $line !== '1')
            ->unique(fn (string $line): string => mb_strtolower($line, 'UTF-8'))
            ->sort(fn (string $a, string $b): int => strnatcasecmp($a, $b))
            ->values()
            ->map(fn (string $name): array => [
                'id' => $name,
                'name' => $name,
            ])
            ->all();

        if ($allLabel) {
            array_unshift($items, ['id' => 'all', 'name' => $allLabel]);
        }

        return $items;
    }
}
