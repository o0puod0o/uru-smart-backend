<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedEmails = config('admin.emails', []);

        if (config('sso.mock')) {
            $allowedEmails[] = config('sso.mock_email');
        }

        $allowedEmails = array_map('strtolower', array_filter($allowedEmails));
        $user = $request->user();
        $email = strtolower((string) ($user ? $user->email : ''));

        abort_unless($email !== '' && in_array($email, $allowedEmails, true), 403);

        return $next($request);
    }
}
