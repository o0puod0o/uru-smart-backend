<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name_th', 'like', "%{$search}%")
                        ->orWhere('last_name_th', 'like', "%{$search}%")
                        ->orWhere('first_name_en', 'like', "%{$search}%")
                        ->orWhere('last_name_en', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('first_name_th')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function show(User $user): View
    {
        $user->load(['department', 'subDepartment']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $departments = Department::query()->orderBy('name')->get();
        $subDepartments = SubDepartment::query()->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'departments', 'subDepartments'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('users', 'code')->ignore($user)],
            'email' => ['nullable', 'email', 'max:255'],
            'prefix_th' => ['nullable', 'string', 'max:20'],
            'first_name_th' => ['required', 'string', 'max:100'],
            'last_name_th' => ['required', 'string', 'max:100'],
            'prefix_en' => ['nullable', 'string', 'max:20'],
            'first_name_en' => ['required', 'string', 'max:100'],
            'last_name_en' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'phone_work' => ['nullable', 'string', 'max:20'],
            'phone_mobile' => ['nullable', 'string', 'max:20'],
            'line_id' => ['nullable', 'string', 'max:50'],
            'facebook' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:200'],
            'bio' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:200'],
            'moo' => ['nullable', 'string', 'max:10'],
            'road' => ['nullable', 'string', 'max:100'],
            'tambon' => ['nullable', 'string', 'max:100'],
            'amphoe' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'zipcode' => ['nullable', 'string', 'max:10'],
            'position' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,dep_id'],
            'sub_dep_id' => ['nullable', 'integer', 'exists:sub_departments,sub_dep_id'],
        ]);

        if (! empty($data['department_id'])) {
            $department = Department::find($data['department_id']);
            $data['department_name_th'] = $department ? $department->name : null;
            $data['department_name_en'] = $department ? $department->name_en : null;
        }

        $user->update($data);

        return redirect()->route('admin.users.show', $user)->with('success', 'บันทึกข้อมูลผู้ใช้แล้ว');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(Auth::user())) {
            return back()->with('error', 'ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้');
        }

        try {
            $user->delete();
        } catch (QueryException) {
            return back()->with('error', 'ไม่สามารถลบผู้ใช้นี้ได้ เนื่องจากยังมีข้อมูลที่เชื่อมโยงอยู่');
        }

        return redirect()->route('admin.users.index')->with('success', 'ลบผู้ใช้แล้ว');
    }
}
