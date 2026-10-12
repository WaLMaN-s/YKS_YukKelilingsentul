<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->isAdmin() && ! $user->is_blocked, 403, 'Anda tidak memiliki akses ke halaman admin.');

        return $next($request);
    }
}
