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
        // Get allowed origins from environment
        $allowedOrigins = array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'https://core.kalaexcel.com,https://www.kalaexcel.com,https://kalaexcel.com')));
        $origin = $request->header('Origin');
        
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }
        
        // Remove any existing CORS headers that might have been set by other middleware
        $response->headers->remove('Access-Control-Allow-Origin');
        $response->headers->remove('Access-Control-Allow-Credentials');
        $response->headers->remove('Access-Control-Allow-Methods');
        $response->headers->remove('Access-Control-Allow-Headers');
        
        // Check if origin is allowed and set specific origin (not wildcard)
        // Also check without protocol and with/without trailing slash
        $originMatched = false;
        if ($origin) {
            // Normalize origin (remove trailing slash)
            $normalizedOrigin = rtrim($origin, '/');
            
            // Check exact match
            if (in_array($normalizedOrigin, $allowedOrigins)) {
                $originMatched = true;
            } else {
                // Also check if any allowed origin matches (case-insensitive)
                foreach ($allowedOrigins as $allowedOrigin) {
                    if (strtolower($normalizedOrigin) === strtolower($allowedOrigin)) {
                        $originMatched = true;
                        $normalizedOrigin = $allowedOrigin; // Use the exact case from config
                        break;
                    }
                }
            }
            
            if ($originMatched) {
                $response->headers->set('Access-Control-Allow-Origin', $normalizedOrigin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin');
                $response->headers->set('Access-Control-Max-Age', '86400');
                $response->headers->set('Vary', 'Origin');
            }
        }
        // If origin is not allowed, don't set any CORS headers
        
        return $response;
    }
}
