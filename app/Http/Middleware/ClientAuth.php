<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class ClientAuth
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('client')->check()) {
            return redirect()->route('client.login')->with('error', 'Please login first.');
        }

        return $next($request);
    }
}
