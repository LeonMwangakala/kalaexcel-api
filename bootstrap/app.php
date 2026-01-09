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
        
        // Our CORS middleware runs LAST to override any wildcard headers
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\CustomCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withEvents(function ($events): void {
        // Override CORS headers after response is prepared to ensure no wildcard
        $events->listen(\Illuminate\Http\Events\ResponsePrepared::class, function ($event) {
            $request = $event->request;
            $response = $event->response;
            $origin = $request->header('Origin');
            
            $allowedOrigins = [
                'https://core.kalaexcel.com',
                'https://www.kalaexcel.com',
                'https://kalaexcel.com',
            ];
            
            if ($origin && in_array($origin, $allowedOrigins)) {
                // Force remove wildcard and set specific origin
                $response->headers->remove('Access-Control-Allow-Origin');
                $response->headers->set('Access-Control-Allow-Origin', $origin, false);
                $response->headers->set('Access-Control-Allow-Credentials', 'true', false);
            }
        });
    })->create();
