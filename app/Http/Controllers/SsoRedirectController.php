<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SsoRedirectController extends Controller
{
    public function url(Request $request)
    {
        $loginUrl = $this->loginUrl($request);

        return response()->json([
            'login_url' => $loginUrl,
            'redirect_uri' => config('sso.redirect_uri'),
            'client_id' => config('sso.client_id'),
            'state' => $request->query('state', 'state'),
        ]);
    }

    public function redirect(Request $request)
    {
        return redirect()->away($this->loginUrl($request));
    }

    private function loginUrl(Request $request): string
    {
        $baseUrl = rtrim((string) config('sso.base_url'), '/');
        $path = trim((string) config('sso.authorize_path'), '/');

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('sso.client_id'),
            'state' => $request->query('state', 'state'),
            'redirect_uri' => $request->query('redirect_uri', config('sso.redirect_uri')),
        ], '', '&', PHP_QUERY_RFC3986);

        return "{$baseUrl}/{$path}?{$query}";
    }
}
