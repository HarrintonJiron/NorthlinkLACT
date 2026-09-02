<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModulePermission
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'No authenticated user');
        }

        // Los administradores tienen acceso a todo
        if ($user->is_admin) {
            return $next($request);
        }

        // Verificar si el usuario tiene el permiso específico del módulo
        $hasPermission = $user->permissions()->where('name', "access_{$module}")->exists();

        if ($hasPermission) {
            return $next($request);
        }

        abort(403, "Missing permission: access_{$module}");
    }
}
