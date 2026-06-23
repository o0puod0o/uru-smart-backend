<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ProfileController
 * จัดการข้อมูล profile ของผู้ใช้
 * - me(): ข้อมูล profile ของ user ที่ login (authenticated)
 * - show(): ข้อมูล profile public (ไม่ต้อง auth) - เฉพาะ public fields
 * - update(): แก้ไข profile ของ user ที่ login
 * - pushToken(): บันทึก push notification token
 */
class ProfileController extends Controller
{
    /**
     * GET /api/profile/{id}
     * ดึงข้อมูล public profile ของผู้ใช้ (ไม่ต้อง authentication)
     *
     * Response: {
     *   "data": {
     *     "id", "full_name_th", "full_name_en", "email", "picture",
     *     "position", "faculty_name_th", "department_name_th", "phone_work", "bio",
     *     "expertises": [{"name": "..."}],
     *     "interests": [{"name": "..."}],
     *     "researches": [{"year": "2567", "name": "..."}],
     *     "journals", "proceedings", "books", "patents", "awards", "lecturers", "trainings"
     *   }
     * }
     *
     * ห้าม expose: citizen_id, phone_mobile, line_id, address, birthdate
     */
    public function show(int $id)
    {
        // Eager load ทั้ง 14 relationships เพื่อลด N+1 query
        // Include nested relationships สำหรับ researches (researchType, researchPMUType, researchLevel)
        $user = User::with([
            'experts',
            'interests',
            'educations',
            'workexes',
            'boardexes',
            'researches.researchType',
            'researches.researchPMUType',
            'researches.researchLevel',
            'journals',
            'proceedings',
            'books',
            'patents',
            'awards',
            'lecturers',
            'trainings',
            'academics',
            'hsps',
        ])->findOrFail($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'full_name_th' => $user->full_name_th,
                'full_name_en' => $user->full_name_en,
                'email' => $user->email,
                'picture' => $user->display_picture,
                'position' => $user->position,
                'faculty_name_th' => $user->faculty_name_th,
                'faculty_name_en' => $user->faculty_name_en,
                'department_name_th' => $user->department_name_th,
                'department_name_en' => $user->department_name_en,
                'campus_id' => $user->campus_id,
                'bio' => $user->bio,
                'website' => $user->website,
                'facebook' => $user->facebook,
                'phone_work' => $user->phone_work,
                'profile_picture' => $user->profile_picture,
                'expertises' => $user->experts->map(fn ($e) => ['name' => $e->name])->values(),
                'interests' => $user->interests->map(fn ($i) => ['name' => $i->name])->values(),
                'researches' => $user->researches->map(fn ($r) => [
                    'year' => $r->year,
                    'name' => $r->name,
                    'research_type_id' => $r->research_type_id,
                    'research_pmu_type_id' => $r->research_PMU_type_id,
                    'research_level_id' => $r->research_level_id,
                    'research_type' => $r->researchType?->name,
                    'research_pmu_type' => $r->researchPMUType?->name,
                    'research_level' => $r->researchLevel?->name,
                ])->values(),
                'journals' => $user->journals->map(fn ($j) => [
                    'year' => $j->year,
                    'name' => $j->name,
                    'url' => $j->url,
                ])->values(),
                'proceedings' => $user->proceedings->map(fn ($p) => [
                    'year' => $p->year,
                    'name' => $p->name,
                ])->values(),
                'books' => $user->books->map(fn ($b) => [
                    'year' => $b->year,
                    'name' => $b->name,
                ])->values(),
                'patents' => $user->patents->map(fn ($pt) => [
                    'year' => $pt->year,
                    'name' => $pt->name,
                    'link' => $pt->link,
                ])->values(),
                'awards' => $user->awards->map(fn ($a) => [
                    'year' => $a->year,
                    'name' => $a->name,
                ])->values(),
                'lecturers' => $user->lecturers->map(fn ($l) => [
                    'year' => $l->year,
                    'name' => $l->name,
                ])->values(),
                'trainings' => $user->trainings->map(fn ($t) => [
                    'year' => $t->year,
                    'name' => $t->name,
                ])->values(),
                'educations' => $user->educations->map(fn ($e) => [
                    'year' => $e->year,
                    'degree' => $e->degree,
                    'course' => $e->course,
                    'university' => $e->university,
                ])->values(),
                'workexes' => $user->workexes->map(fn ($w) => [
                    'position' => $w->position,
                    'workplace' => $w->workplace,
                    'year_start' => $w->year_start,
                    'year_end' => $w->year_end,
                ])->values(),
                'boardexes' => $user->boardexes->map(fn ($b) => [
                    'position' => $b->position,
                    'workplace' => $b->workplace,
                    'year_start' => $b->year_start,
                    'year_end' => $b->year_end,
                ])->values(),
                'academics' => $user->academics->map(fn ($ac) => [
                    'year' => $ac->year,
                    'name' => $ac->name,
                ])->values(),
                'hsps' => $user->hsps->map(fn ($h) => [
                    'year' => $h->year,
                    'name' => $h->name,
                ])->values(),
            ]
        ]);
    }

    /**
     * GET /api/me
     * ดึงข้อมูล profile เต็มของ user ที่ login (authenticated)
     * Include: SSO data + editable fields + position_type mapping
     * Requires: auth:sanctum middleware
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                // ข้อมูลจาก SSO
                'id'                 => $user->id,
                'code'               => $user->code,
                'username'           => $user->username,
                'citizen_id'         => $user->citizen_id,
                'passport_id'        => $user->passport_id,
                'full_name_th'       => $user->full_name_th,
                'full_name_en'       => $user->full_name_en,
                'email'              => $user->email,
                'gender'             => $user->gender,
                'picture'            => $user->picture,
            'type'               => $user->type,
            'degree'             => $user->degree,
            'status'             => $user->status,
            'faculty_id'         => $user->faculty_id,
            'faculty_name_th'    => $user->faculty_name_th,
            'department_id'      => $user->department_id,
            'department_name_th' => $user->department_name_th,
            'campus_id'          => $user->campus_id,

            // ข้อมูลที่แก้ไขได้
            'prefix_id'          => $user->prefix_th,
            'birthdate'          => $user->birth_date?->format('Y-m-d'),
            'phone_work'         => $user->phone_work,
            'phone_mobile'       => $user->phone_mobile,
            'line_id'            => $user->line_id,
            'facebook'           => $user->facebook,
            'website'            => $user->website,
            'bio'                => $user->bio,
            'picture'            => $user->display_picture,
            'address'            => $user->address,
            'moo'                => $user->moo,
            'road'               => $user->road,
            'tambon'             => $user->tambon,
            'amphoe'             => $user->amphoe,
            'province'           => $user->province,
            'zipcode'            => $user->zipcode,
            'position'           => $user->position,
            'position_type'      => $this->getPositionType($user->position),
            'branch'             => $user->branch,
            'main_unit'          => $user->department_id,
            'sub_unit'           => $user->sub_dep_id,
            'sub_unit_id'        => $user->sub_dep_id,
            ],
        ]);
    }

    /**
     * PUT /api/me
     * แก้ไข profile ของ user ที่ login
     * Field mapping: frontend name → database column
     *   - prefix_id → prefix_th
     *   - birthdate → birth_date
     *   - main_unit → department_id
     *   - sub_unit / sub_unit_id → sub_dep_id
     * Requires: auth:sanctum middleware
     */
    public function update(Request $request)
    {
        $request->validate([
            'prefix_id'       => 'nullable|string|max:20',
            'birthdate'       => 'nullable|date',
            'phone_work'      => 'nullable|string|max:20',
            'phone_mobile'    => 'nullable|string|max:20',
            'line_id'         => 'nullable|string|max:50',
            'facebook'        => 'nullable|string|max:100',
            'website'         => 'nullable|url|max:200',
            'bio'             => 'nullable|string|max:500',
            'profile_picture' => 'nullable|string',
            'address'         => 'nullable|string|max:200',
            'moo'             => 'nullable|string|max:10',
            'road'            => 'nullable|string|max:100',
            'tambon'          => 'nullable|string|max:100',
            'amphoe'          => 'nullable|string|max:100',
            'province'        => 'nullable|string|max:100',
            'zipcode'         => 'nullable|string|max:10',
            'position'        => 'nullable|string|max:100',
            'branch'          => 'nullable|string|max:100',
            'main_unit'       => 'nullable|integer|exists:departments,dep_id',
            'sub_unit'        => 'nullable|integer|exists:sub_departments,sub_dep_id',
            'sub_unit_id'     => 'nullable|integer|exists:sub_departments,sub_dep_id',
        ]);

        $user = $request->user();
        $data = $request->only([
            'phone_work', 'phone_mobile', 'line_id',
            'facebook', 'website', 'bio', 'profile_picture',
            'address', 'moo', 'road', 'tambon',
            'amphoe', 'province', 'zipcode',
            'position', 'branch',
        ]);

        // frontend ส่ง prefix_id → เก็บใน prefix_th
        if ($request->has('prefix_id')) {
            $data['prefix_th'] = $request->input('prefix_id');
        }

        // frontend ส่ง birthdate → เก็บใน birth_date
        if ($request->has('birthdate')) {
            $data['birth_date'] = $request->input('birthdate');
        }

        // main_unit → department_id + ดึงชื่อมาด้วย
        if ($request->filled('main_unit')) {
            $data['department_id'] = $request->input('main_unit');
            $department = Department::find($request->input('main_unit'));
            if ($department) {
                $data['department_name_th'] = $department->name;
                $data['department_name_en'] = $department->name_en;
            }
        }

        // รองรับทั้ง sub_unit และ sub_unit_id
        $subUnit = $request->input('sub_unit_id') ?? $request->input('sub_unit');
        if ($subUnit) {
            $data['sub_dep_id'] = $subUnit;

            // อัปเดต sub_department_name ถ้ามี
            $sub = SubDepartment::find($subUnit);
            if ($sub) {
                $data['department_name_th'] = $data['department_name_th']
                    ?? $user->department_name_th;
            }
        }

        $user->update($data);

        return response()->json([
            'message' => 'อัพเดทข้อมูลสำเร็จ',
            'user'    => $this->me($request)->getData(),
        ]);
    }

    /**
     * Helper: Map position name → position_type (line)
     * ใช้สำหรับ me() endpoint เพื่อ return position_type field
     * สายวิชาการ: อาจารย์, ผศ., รศ., ศ., หัวหน้าภาควิชา, นักวิชาการ, ครู
     * สายสนับสนุน: เจ้าหน้าที่, ช่าง, พนักงาน, ฯลฯ
     */
    private function getPositionType(?string $position): ?string
    {
        if (!$position) {
            return null;
        }

        // Map position ไปที่ lines (สายวิชาการ, สายสนับสนุน)
        $lineMap = [
            'อาจารย์'          => 'สายวิชาการ',
            'ผู้ช่วยศาสตราจารย์' => 'สายวิชาการ',
            'รองศาสตราจารย์'   => 'สายวิชาการ',
            'ศาสตราจารย์'      => 'สายวิชาการ',
            'หัวหน้าภาควิชา'     => 'สายวิชาการ',
            'เจ้าหน้าที่'        => 'สายสนับสนุน',
            'นักวิชาการ'        => 'สายวิชาการ',
        ];

        return $lineMap[$position] ?? null;
    }

    /**
     * GET /api/profile/{id}/pdf
     * ดาวน์โหลด profile เป็น PDF
     * Return: PDF file (application/pdf)
     * Requires: auth:sanctum middleware (optional - can be public)
     */
    public function pdf(int $id)
    {
        $user = User::with([
            'experts',
            'interests',
            'educations',
            'workexes',
            'boardexes',
            'researches.researchType',
            'researches.researchPMUType',
            'researches.researchLevel',
            'journals',
            'proceedings',
            'books',
            'patents',
            'awards',
            'lecturers',
            'trainings',
            'academics',
            'hsps',
        ])->findOrFail($id);

        // Build data array (same as show() method)
        $data = [
            'id' => $user->id,
            'full_name_th' => $user->full_name_th,
            'full_name_en' => $user->full_name_en,
            'email' => $user->email,
            'picture' => $this->profilePictureForPdf($user->display_picture),
            'position' => $user->position,
            'faculty_name_th' => $user->faculty_name_th,
            'faculty_name_en' => $user->faculty_name_en,
            'department_name_th' => $user->department_name_th,
            'department_name_en' => $user->department_name_en,
            'campus_id' => $user->campus_id,
            'bio' => $user->bio,
            'website' => $user->website,
            'facebook' => $user->facebook,
            'phone_work' => $user->phone_work,
            'profile_picture' => $user->profile_picture,
            'expertises' => $user->experts->map(fn ($e) => ['name' => $e->name])->values(),
            'interests' => $user->interests->map(fn ($i) => ['name' => $i->name])->values(),
            'researches' => $user->researches->map(fn ($r) => [
                'year' => $r->year,
                'name' => $r->name,
                'research_type_id' => $r->research_type_id,
                'research_pmu_type_id' => $r->research_PMU_type_id,
                'research_level_id' => $r->research_level_id,
                'research_type' => $r->researchType?->name,
                'research_pmu_type' => $r->researchPMUType?->name,
                'research_level' => $r->researchLevel?->name,
            ])->values(),
            'journals' => $user->journals->map(fn ($j) => [
                'year' => $j->year,
                'name' => $j->name,
                'url' => $j->url,
            ])->values(),
            'proceedings' => $user->proceedings->map(fn ($p) => [
                'year' => $p->year,
                'name' => $p->name,
            ])->values(),
            'books' => $user->books->map(fn ($b) => [
                'year' => $b->year,
                'name' => $b->name,
            ])->values(),
            'patents' => $user->patents->map(fn ($pt) => [
                'year' => $pt->year,
                'name' => $pt->name,
                'link' => $pt->link,
            ])->values(),
            'awards' => $user->awards->map(fn ($a) => [
                'year' => $a->year,
                'name' => $a->name,
            ])->values(),
        ];

        // Return HTML for frontend to handle PDF conversion
        $html = view('profile-pdf', ['user' => $data])->render();

        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="profile-' . $user->full_name_th . '.html"');
    }

    /**
     * POST /api/push-token
     * บันทึก push notification token สำหรับ user ที่ login
     * ใช้สำหรับส่ง push notification ไปยัง device
     * Requires: auth:sanctum middleware
     */
    public function pushToken(Request $request)
    {
        $request->validate([
            'push_token' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $user->push_token = $request->input('push_token');
        $user->save();

        return response()->json([
            'message' => 'Push token saved successfully',
        ]);
    }

    /**
     * Convert locally stored profile images to data URIs so frontend PDF tools
     * do not need to re-fetch /storage images from a different origin.
     */
    private function profilePictureForPdf(?string $picture): ?string
    {
        if (empty($picture)) {
            return null;
        }

        if (Str::startsWith($picture, 'data:')) {
            return $picture;
        }

        $path = parse_url($picture, PHP_URL_PATH) ?: $picture;

        if (Str::startsWith($path, '/storage/')) {
            $storagePath = Str::after($path, '/storage/');
        } elseif (Str::startsWith($path, 'storage/')) {
            $storagePath = Str::after($path, 'storage/');
        } elseif (Str::startsWith($path, 'photos/')) {
            $storagePath = $path;
        } else {
            return Str::startsWith($picture, '/')
                ? url($picture)
                : $picture;
        }

        if (!Storage::disk('public')->exists($storagePath)) {
            return Str::startsWith($picture, '/')
                ? url($picture)
                : $picture;
        }

        $mimeType = Storage::disk('public')->mimeType($storagePath) ?: 'image/jpeg';
        $contents = Storage::disk('public')->get($storagePath);

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    /**
     * POST /api/me/photo
     * อัปโหลดรูปโปรไฟล์ของ user ที่ login
     * Requires: auth:sanctum middleware
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $user = $request->user();

        // Delete old photo if exists
        if ($user->picture) {
            try {
                $oldPath = str_replace('/storage/', '', parse_url($user->picture, PHP_URL_PATH));
                \Storage::disk('public')->delete($oldPath);
            } catch (\Exception $e) {
                // Ignore if file not found
            }
        }

        // Store new photo
        $path = $request->file('photo')->store('photos', 'public');
        $url = \Storage::disk('public')->url($path);

        // Update user picture
        $user->update(['picture' => $url]);

        return response()->json([
            'data' => [
                'photo_url' => $url,
            ],
        ]);
    }

    /**
     * DELETE /api/me/photo
     * ลบรูปโปรไฟล์ของ user ที่ login
     * Requires: auth:sanctum middleware
     */
    public function deletePhoto(Request $request)
    {
        $user = $request->user();

        // Delete photo file if exists
        if ($user->picture) {
            try {
                $path = str_replace('/storage/', '', parse_url($user->picture, PHP_URL_PATH));
                \Storage::disk('public')->delete($path);
            } catch (\Exception $e) {
                // Ignore if file not found
            }

            // Update user picture to null
            $user->update(['picture' => null]);
        }

        return response()->json([
            'message' => 'Photo deleted successfully',
        ]);
    }
}
