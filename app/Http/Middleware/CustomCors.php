<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');
        
        $allowedOrigins = [
            'https://core.kalaexcel.com',
            'https://www.kalaexcel.com',
            'https://kalaexcel.com',
        ];
        
        // Handle preflight OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
            
            if ($origin && in_array($origin, $allowedOrigins)) {
                // Remove any existing CORS headers
                $response->headers->remove('Access-Control-Allow-Origin');
                $response->headers->remove('Access-Control-Allow-Credentials');
                
                // Set correct headers for preflight
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin');
                $response->headers->set('Access-Control-Max-Age', '86400');
            }
            
            return $response;
        }
        
        // For non-OPTIONS requests, get the response first
        $response = $next($request);
        
        // Then override CORS headers
        if ($origin && in_array($origin, $allowedOrigins)) {
            // Force remove ALL CORS headers
            $allHeaders = $response->headers->all();
            foreach (array_keys($allHeaders) as $headerName) {
                if (stripos($headerName, 'access-control') === 0) {
                    $response->headers->remove($headerName);
                }
            }
            
            // Set correct headers
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin');
            $response->headers->set('Access-Control-Max-Age', '86400');
            $response->headers->set('Vary', 'Origin');
        }
        
        return $response;
    }
}
