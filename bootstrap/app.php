<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Remove Laravel's default CORS middleware to prevent wildcard
        $middleware->api(remove: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        $middleware->web(remove: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        // Apply CORS middleware to both API and Web routes (Sanctum uses web routes)
        $middleware->api(append: [
            \App\Http\Middleware\CustomCors::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\CustomCors::class,
        ]);
        
        // Sanctum middleware
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
