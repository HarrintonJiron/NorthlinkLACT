<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureAdministrator;
use App\Http\Middleware\EnsureCompanyScope;
use App\Http\Middleware\EnsureModulePermission;
use App\Http\Middleware\EnsurePlantScope;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        $middleware->alias([
            'company.scope' => EnsureCompanyScope::class,
            'plant.scope' => EnsurePlantScope::class,
            'active' => EnsureActiveUser::class,
            'user.active' => EnsureUserIsActive::class,
            'administrator' => EnsureAdministrator::class,
            'module.permission' => EnsureModulePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
