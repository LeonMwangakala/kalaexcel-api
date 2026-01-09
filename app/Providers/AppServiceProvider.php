<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Response;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Override CORS headers using response macro - runs on every response
        Response::macro('withCors', function ($origin) {
            $allowedOrigins = ['https://core.kalaexcel.com', 'https://www.kalaexcel.com', 'https://kalaexcel.com'];
            
            if (in_array($origin, $allowedOrigins)) {
                // Remove ALL CORS headers
                $allHeaders = $this->headers->all();
                foreach (array_keys($allHeaders) as $headerName) {
                    if (stripos($headerName, 'access-control') === 0) {
                        $this->headers->remove($headerName);
                    }
                }
                
                // Set correct headers
                $this->headers->set('Access-Control-Allow-Origin', $origin);
                $this->headers->set('Access-Control-Allow-Credentials', 'true');
                $this->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
                $this->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin');
                $this->headers->set('Access-Control-Max-Age', '86400');
            }
            
            return $this;
        });
        
        // Listen to response prepared event and force override
        $this->app['events']->listen(\Illuminate\Http\Events\ResponsePrepared::class, function ($event) {
            $request = $event->request;
            $response = $event->response;
            $origin = $request->header('Origin');
            
            $allowedOrigins = ['https://core.kalaexcel.com', 'https://www.kalaexcel.com', 'https://kalaexcel.com'];
            
            if ($origin && in_array($origin, $allowedOrigins)) {
                // Get current headers
                $currentHeaders = $response->headers->all();
                $newHeaders = [];
                
                // Copy all non-CORS headers
                foreach ($currentHeaders as $key => $value) {
                    if (stripos($key, 'access-control') !== 0) {
                        $newHeaders[$key] = $value;
                    }
                }
                
                // Replace all headers (removes CORS)
                $response->headers->replace($newHeaders);
                
                // Add correct CORS headers
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin');
                $response->headers->set('Access-Control-Max-Age', '86400');
            }
        });
    }
}
