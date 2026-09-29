<?php

use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Applied to every request, API or web: baseline hardening headers
        // (X-Frame-Options, CSP, etc). See app/Http/Middleware/SecurityHeaders.php.
        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            // Needed for Auth::logoutOtherDevices() (used when a password
            // is changed) to actually invalidate sessions on other devices.
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            EnsureUserIsNotBlocked::class,
        ]);

        // Route middleware aliases used throughout routes/web.php.
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Baseline throttle for every web request, as defense in depth
        // against scraping/automation. The 'global' limiter is defined in
        // AppServiceProvider. Sensitive routes (login, register, password
        // reset) additionally use their own, much stricter limiter.
        $middleware->web(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':global',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Never leak stack traces or query details once debug mode is off.
        // This is Laravel's default behaviour (driven by APP_DEBUG), kept
        // explicit here as a reminder: make sure APP_DEBUG=false in any
        // environment other than your own machine.
    })->create();
