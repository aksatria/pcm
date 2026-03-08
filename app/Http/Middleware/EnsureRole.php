<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $role = strtolower(trim($role));
        $hasRole = method_exists($user, 'hasRole') ? $user->hasRole($role) : ((string) ($user->role ?? '') === $role);

        if (!$hasRole) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        return $next($request);
    }
}
