<?php

namespace App\Listeners;

use Illuminate\Http\Events\ResponsePrepared;

class OverrideCorsHeaders
{
    /**
     * Handle the event.
     */
    public function handle(ResponsePrepared $event): void
    {
        $request = $event->request;
        $response = $event->response;
        $origin = $request->header('Origin');
        
        $allowedOrigins = [
            'https://core.kalaexcel.com',
            'https://www.kalaexcel.com',
            'https://kalaexcel.com',
        ];
        
        if ($origin && in_array($origin, $allowedOrigins)) {
            // Get all headers and remove any CORS headers
            $headers = $response->headers->all();
            foreach ($headers as $key => $value) {
                if (stripos($key, 'access-control') === 0) {
                    $response->headers->remove($key);
                }
            }
            
            // Force set specific origin (NOT wildcard)
            $response->headers->set('Access-Control-Allow-Origin', $origin, true);
            $response->headers->set('Access-Control-Allow-Credentials', 'true', true);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS', true);
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin', true);
            $response->headers->set('Access-Control-Max-Age', '86400', true);
        }
    }
}
