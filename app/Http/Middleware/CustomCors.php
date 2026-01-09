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
            // Get all headers as array and remove ALL CORS headers (case-insensitive)
            $allHeaders = $response->headers->all();
            foreach (array_keys($allHeaders) as $headerName) {
                if (stripos($headerName, 'access-control') === 0) {
                    $response->headers->remove($headerName);
                }
            }
            
            // Now set the correct headers with specific origin (NOT wildcard)
            $response->headers->set('Access-Control-Allow-Origin', $origin, false);
            $response->headers->set('Access-Control-Allow-Credentials', 'true', false);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS', false);
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin', false);
            $response->headers->set('Access-Control-Max-Age', '86400', false);
            $response->headers->set('Vary', 'Origin', false);
        }
        
        return $response;
    }
}
