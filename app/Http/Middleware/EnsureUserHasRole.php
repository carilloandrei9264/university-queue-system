<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->is_active && in_array($user->role, $roles, true),
            403,
        );

        if ($user->role === 'staff') {
            abort_if($user->department_id === null, 403);
        }

        return $next($request);
    }
}
