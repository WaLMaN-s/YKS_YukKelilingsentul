<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /** Contoh: permission:rental,pickup  (cukup salah satu). */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        abort_unless($request->user()?->hasAnyPermission($permissions), 403, 'Role Anda tidak memiliki izin untuk fitur ini.');

        return $next($request);
    }
}
