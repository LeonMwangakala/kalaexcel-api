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
        $allowedOrigins = ['https://core.kalaexcel.com', 'https://www.kalaexcel.com', 'https://kalaexcel.com'];
        
        // Handle preflight OPTIONS requests FIRST - before any other middleware
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 204);
            
            if ($origin && in_array($origin, $allowedOrigins)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin');
                $response->headers->set('Access-Control-Max-Age', '86400');
            }
            
            return $response;
        }
        
        // For other requests, process normally then fix headers
        $response = $next($request);
        
        if ($origin && in_array($origin, $allowedOrigins)) {
            // Remove ALL CORS headers completely
            $allHeaders = $response->headers->all();
            $cleanHeaders = [];
            
            foreach ($allHeaders as $key => $value) {
                if (stripos($key, 'access-control') !== 0) {
                    $cleanHeaders[$key] = $value;
                }
            }
            
            // Replace headers (removes all CORS)
            $response->headers->replace($cleanHeaders);
            
            // Add correct CORS headers
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
