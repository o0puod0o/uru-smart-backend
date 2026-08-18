<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureApiAdmin {
    public function handle(Request $request, Closure $next){ $user=$request->user(); abort_unless($user && $user->isAdmin(),403,'Admin permission required.'); return $next($request); }
}
