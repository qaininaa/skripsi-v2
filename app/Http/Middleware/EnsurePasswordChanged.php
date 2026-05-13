<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role === 'super') {
            return $next($request);
        }

        if ($request->routeIs('password.change.*')) {
            if ($user->password_changed_at !== null) {
                return redirect()->route('dashboard');
            }

            return $next($request);
        }

        if ($user->password_changed_at === null) {
            return redirect()->route('password.change.form');
        }

        return $next($request);
    }
}
