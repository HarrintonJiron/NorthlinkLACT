<?php

use App\Http\Middleware\EnsureActiveOfflineDevice;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureAdministrator;
use App\Http\Middleware\EnsureCompanyScope;
use App\Http\Middleware\EnsureModulePermission;
use App\Http\Middleware\EnsurePlantScope;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
        $middleware->redirectGuestsTo(fn (Request $request): string => config('app.force_https')
            ? rtrim(config('app.url'), '/').'/login'
            : '/login');
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
            'offline.device' => EnsureActiveOfflineDevice::class,
            'module.permission' => EnsureModulePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $loginUrl = config('app.force_https')
                ? rtrim(config('app.url'), '/').'/login'
                : '/login';

            return redirect()->to($loginUrl);
        });

        $exceptions->respond(function (Response $response): Response {
            $location = $response->headers->get('Location');

            if (config('app.force_https')
                && is_string($location)
                && str_starts_with($location, 'http://')) {
                $response->headers->set('Location', 'https://'.substr($location, 7));
            }

            if (in_array($response->getStatusCode(), [403, 404, 500, 503], true)
                && request()->header('X-Inertia')) {
                $content = match ($response->getStatusCode()) {
                    403 => ['Acceso denegado', 'No tienes permiso para realizar esta operación.'],
                    404 => ['Página no encontrada', 'La página o registro solicitado no existe.'],
                    503 => ['Servicio temporalmente no disponible', 'Estamos realizando mantenimiento. Intenta nuevamente en unos minutos.'],
                    default => ['Ocurrió un problema', 'El incidente fue registrado. Intenta nuevamente o contacta al administrador.'],
                };

                return Inertia::render('Error', ['status' => $response->getStatusCode(), 'title' => $content[0], 'message' => $content[1]])
                    ->toResponse(request())
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
