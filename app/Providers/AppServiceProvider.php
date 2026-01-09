<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override CORS headers in response after it's prepared
        $this->app['events']->listen(\Illuminate\Http\Events\ResponsePrepared::class, function ($event) {
            $request = $event->request;
            $response = $event->response;
            $origin = $request->header('Origin');
            
            $allowedOrigins = ['https://core.kalaexcel.com', 'https://www.kalaexcel.com', 'https://kalaexcel.com'];
            
            if ($origin && in_array($origin, $allowedOrigins)) {
                // Remove wildcard header completely
                $response->headers->remove('Access-Control-Allow-Origin');
                // Set specific origin
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }
        });
    }
}
