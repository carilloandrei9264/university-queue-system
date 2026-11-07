<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedRoutes = ['password.change', 'password.change.update', 'logout'];

        if ($request->user()?->must_change_password
            && ! in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
