<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $companyId = $request->route('company') ?? $request->input('company_id');

        if ($companyId && !$user->companies()->where('companies.id', $companyId)->exists()) {
            abort(403, 'No tienes acceso a esta empresa.');
        }

        return $next($request);
    }
}
