<?php

namespace App\Http\Controllers;

use App\Services\SSOService;
use App\Services\SSOUserSynchronizer;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class WebAdminAuthController extends Controller
{
    public function __construct(
        private SSOService $sso,
        private SSOUserSynchronizer $users
    ) {
    }

    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function redirectToSso(Request $request): RedirectResponse
    {
        $state = bin2hex(random_bytes(20));
        $request->session()->put('admin_sso_state', $state);

        return redirect()->route('auth.redirect', [
            'state' => 'admin:'.$state,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $token = $this->sso->loginWithPassword($credentials['email'], $credentials['password']);
            $ssoUser = $this->sso->getUserInfo($token['access_token']);
            $user = $this->users->sync($ssoUser);

            if (! AdminAccess::allows($user)) {
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'บัญชีนี้ไม่มีสิทธิ์เข้าใช้งานระบบจัดการ']);
            }

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง']);
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
