<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Ensure the authenticated user has one of the allowed roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        $userRole = strtolower((string) optional($user->employee)->role);
        $allowedRoles = array_map(static fn($role) => strtolower((string) $role), $roles);

        if (empty($allowedRoles) || in_array($userRole, $allowedRoles, true)) {
            return $next($request);
        }

        abort(403, 'You are not authorized to access this resource.');
    }
}
