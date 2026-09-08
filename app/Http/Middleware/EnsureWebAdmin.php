<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\AdminAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && AdminAccess::allows($user), 403);

        return $next($request);
    }
}
