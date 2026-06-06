<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        // Trust the ngrok/reverse-proxy so Laravel honours X-Forwarded-Proto (https).
        // Without this, requests behind ngrok look like http and route()/url() generate
        // http:// links on an https page -> the browser blocks uploads as mixed content.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'activity' => \App\Http\Middleware\UpdateLastActivity::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Aktifkan AuthenticateSession agar Auth::logoutOtherDevices() benar-benar
        // memutus session lain saat user ganti password.
        $middleware->web(append: [
            \Illuminate\Session\Middleware\AuthenticateSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
