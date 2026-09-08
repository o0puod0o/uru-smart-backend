<?php

namespace App\Http\Controllers;

use App\Models\AdminAccount;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAccountController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $admins = AdminAccount::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.accounts.index', compact('admins', 'search'));
    }

    public function create(): View
    {
        return view('admin.accounts.form', ['admin' => new AdminAccount()]);
    }

    public function store(Request $request, AdminAuditService $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['password'] = Hash::make($data['password']);

        $admin = AdminAccount::create($data);
        $audit->recordModel($request, 'admin_account_created', $admin);

        return redirect()->route('admin.accounts.index')->with('success', 'สร้างบัญชีผู้ดูแลแล้ว');
    }

    public function edit(AdminAccount $admin): View
    {
        return view('admin.accounts.form', compact('admin'));
    }

    public function update(Request $request, AdminAccount $admin, AdminAuditService $audit): RedirectResponse
    {
        $data = $this->validated($request, $admin);
        $data['is_active'] = $request->boolean('is_active');
        $current = $request->user('admin');

        if ($admin->is($current) && (! $data['is_active'] || $data['role'] !== $admin->role)) {
            return back()->withInput()->with('error', 'บัญชีที่กำลังใช้งานอยู่ไม่สามารถลดสิทธิ์หรือปิดใช้งานตัวเองได้');
        }

        if ($admin->isSuperAdmin() && $data['role'] !== 'super_admin'
            && AdminAccount::query()->where('role', 'super_admin')->count() < 2) {
            return back()->withInput()->with('error', 'ต้องมีบัญชี super admin อย่างน้อยหนึ่งบัญชี');
        }

        $before = $admin->toArray();
        if (blank($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $admin->update($data);
        $audit->recordModel($request, 'admin_account_updated', $admin, $before);

        return redirect()->route('admin.accounts.index')->with('success', 'บันทึกบัญชีผู้ดูแลแล้ว');
    }

    private function validated(Request $request, ?AdminAccount $admin = null): array
    {
        $password = $admin
            ? ['nullable', 'string', 'min:12', 'confirmed']
            : ['required', 'string', 'min:12', 'confirmed'];

        return $request->validate([
            'username' => ['required', 'string', 'alpha_dash', 'max:80', Rule::unique('admin_accounts', 'username')->ignore($admin)],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('admin_accounts', 'email')->ignore($admin)],
            'role' => ['required', Rule::in(['super_admin', 'admin', 'editor'])],
            'is_active' => ['nullable', 'boolean'],
            'password' => $password,
        ]);
    }
}
