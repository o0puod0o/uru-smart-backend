<?php

namespace App\Http\Controllers;

use App\Models\AdminAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $key = 'admin-login:'.$request->ip().':'.mb_strtolower($data['username']);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'มีการลองเข้าสู่ระบบมากเกินไป กรุณารอสักครู่แล้วลองใหม่']);
        }

        $admin = AdminAccount::query()->where('username', $data['username'])->first();

        if (! $admin || ! $admin->is_active || ! Hash::check($data['password'], $admin->password)) {
            RateLimiter::hit($key, 60);

            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
        }

        RateLimiter::clear($key);
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'ออกจากระบบผู้ดูแลแล้ว');
    }
}
