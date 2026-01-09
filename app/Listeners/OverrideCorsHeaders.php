<?php

namespace App\Listeners;

use Illuminate\Http\Events\ResponsePrepared;

class OverrideCorsHeaders
{
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
            // Get all headers
            $allHeaders = $response->headers->all();
            
            // Remove ALL CORS headers (check all possible variations)
            foreach (array_keys($allHeaders) as $headerName) {
                if (stripos($headerName, 'access-control') === 0) {
                    $response->headers->remove($headerName);
                }
            }
            
            // Also try removing with exact names
            $response->headers->remove('Access-Control-Allow-Origin');
            $response->headers->remove('access-control-allow-origin');
            
            // Force set the correct origin (this should override anything)
            $response->headers->set('Access-Control-Allow-Origin', $origin, true);
            $response->headers->set('Access-Control-Allow-Credentials', 'true', true);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS', true);
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin', true);
            $response->headers->set('Access-Control-Max-Age', '86400', true);
        }
    }
}
