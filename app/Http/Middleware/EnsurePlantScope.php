<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $plantId = $request->route('plant') ?? $request->input('plant_id');

        if ($plantId && !$user->plants()->where('plants.id', $plantId)->exists()) {
            abort(403, 'No tienes acceso a esta planta.');
        }

        return $next($request);
    }
}
