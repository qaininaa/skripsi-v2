<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    // Tambahkan parameter variadic ...$roles di akhir fungsi
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Cek apakah role user saat ini ada di dalam daftar parameter yang dikirim route
        if (!in_array($user->role, $roles)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}