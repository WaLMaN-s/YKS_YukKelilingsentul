<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Area pemesanan customer (booking, checkout, pembayaran, dashboard) hanya untuk akun ber-role customer.
 * Akun admin/staf mengelola pesanan lewat dashboard admin — bukan memesan dari website.
 */
class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isCustomer()) {
            return $next($request);
        }

        $message = 'Halaman pemesanan hanya untuk akun customer. Akun admin/staf mengelola pesanan lewat dashboard admin.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        if ($user?->isAdmin()) {
            return redirect()->route('admin.dashboard')->with('error', $message);
        }

        abort(403, $message);
    }
}
