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
        
        // Allowed origins
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
            // Remove ALL existing CORS headers first
            $response->headers->remove('Access-Control-Allow-Origin');
            $response->headers->remove('Access-Control-Allow-Credentials');
            $response->headers->remove('Access-Control-Allow-Methods');
            $response->headers->remove('Access-Control-Allow-Headers');
            $response->headers->remove('Access-Control-Max-Age');
            
            // Set the correct CORS headers with specific origin (NOT wildcard)
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
