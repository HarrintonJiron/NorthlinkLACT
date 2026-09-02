<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModulePermission
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless($request->user()?->hasPermission("access_{$module}"), 403);

        return $next($request);
    }
}
