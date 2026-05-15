<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Pakai di route: ->middleware('role:teacher') atau 'role:student' / 'role:admin'.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::user();

        $isAllowed = match ($role) {
            'admin'   => $user?->isAdmin(),
            'teacher' => $user?->isTeacher(),
            'student' => $user?->isStudent(),
            default   => false,
        };

        abort_unless($isAllowed, 403, 'Akses ditolak: peran Anda tidak diizinkan untuk halaman ini.');

        return $next($request);
    }
}
