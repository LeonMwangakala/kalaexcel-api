<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');
        
        // Allowed origins - hardcoded for simplicity
        $allowedOrigins = [
            'https://core.kalaexcel.com',
            'https://www.kalaexcel.com',
            'https://kalaexcel.com',
        ];
        
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }
        
        // Check if origin is allowed
        if ($origin && in_array($origin, $allowedOrigins)) {
            // Get all headers as array
            $headers = $response->headers->all();
            
            // Remove ALL CORS-related headers (case-insensitive)
            foreach ($headers as $key => $value) {
                if (stripos($key, 'access-control') === 0) {
                    $response->headers->remove($key);
                }
            }
            
            // Now set the correct headers with specific origin (NOT wildcard)
            $response->headers->set('Access-Control-Allow-Origin', $origin, true);
            $response->headers->set('Access-Control-Allow-Credentials', 'true', true);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS', true);
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin', true);
            $response->headers->set('Access-Control-Max-Age', '86400', true);
            $response->headers->set('Vary', 'Origin', true);
        }
        
        return $response;
    }
}
