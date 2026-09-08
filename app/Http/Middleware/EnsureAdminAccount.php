<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if (! $admin || ! $admin->is_active) {
            Auth::guard('admin')->logout();

            return redirect()->route('login')->with('error', 'บัญชีผู้ดูแลไม่พร้อมใช้งาน');
        }

        return $next($request);
    }
}
