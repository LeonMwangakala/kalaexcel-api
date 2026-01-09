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
        // Override CORS headers in response after it's prepared - FINAL OVERRIDE
        $this->app['events']->listen(\Illuminate\Http\Events\ResponsePrepared::class, function ($event) {
            $request = $event->request;
            $response = $event->response;
            $origin = $request->header('Origin');
            
            $allowedOrigins = ['https://core.kalaexcel.com', 'https://www.kalaexcel.com', 'https://kalaexcel.com'];
            
            if ($origin && in_array($origin, $allowedOrigins)) {
                // Get all headers and rebuild without wildcard
                $allHeaders = $response->headers->all();
                $newHeaders = [];
                
                foreach ($allHeaders as $key => $value) {
                    // Skip CORS headers - we'll add them back correctly
                    if (stripos($key, 'access-control') !== 0) {
                        $newHeaders[$key] = $value;
                    }
                }
                
                // Replace all headers (this removes CORS headers)
                $response->headers->replace($newHeaders);
                
                // Now add correct CORS headers
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin');
                $response->headers->set('Access-Control-Max-Age', '86400');
            }
        });
    }
}
