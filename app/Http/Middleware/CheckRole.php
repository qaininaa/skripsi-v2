<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts route access to users with one of the specified roles.
 *
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request       
     * @param  Closure  $next          
     * @param  string   ...$roles      
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!in_array($user->role, $roles, true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
