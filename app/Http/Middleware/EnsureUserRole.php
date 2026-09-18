<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Akses ditolak.');
        }

        // Administrator (super admin) memiliki akses ke semua halaman.
        if ($user->role === 'administrator') {
            return $next($request);
        }

        $hasSalesAccess = $user->isSales() && (in_array('sales', $roles, true) || in_array('sales_admin', $roles, true));
        if (! empty($roles) && ! in_array($user->role, $roles, true) && ! $hasSalesAccess) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
