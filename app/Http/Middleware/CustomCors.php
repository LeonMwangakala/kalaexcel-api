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
        
        // Check if origin is allowed
        if ($origin) {
            // Normalize origin (remove trailing slash)
            $normalizedOrigin = rtrim($origin, '/');
            
            // Check if origin is in allowed list (case-insensitive)
            $originMatched = false;
            $matchedOrigin = null;
            
            foreach ($allowedOrigins as $allowedOrigin) {
                if (strtolower($normalizedOrigin) === strtolower($allowedOrigin)) {
                    $originMatched = true;
                    $matchedOrigin = $allowedOrigin; // Use exact case from config
                    break;
                }
            }
            
            if ($originMatched) {
                // Force remove existing CORS headers (including wildcard)
                $response->headers->remove('Access-Control-Allow-Origin');
                $response->headers->remove('Access-Control-Allow-Credentials');
                $response->headers->remove('Access-Control-Allow-Methods');
                $response->headers->remove('Access-Control-Allow-Headers');
                
                // Set correct CORS headers with specific origin
                $response->headers->set('Access-Control-Allow-Origin', $matchedOrigin, false);
                $response->headers->set('Access-Control-Allow-Credentials', 'true', false);
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS', false);
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin', false);
                $response->headers->set('Access-Control-Max-Age', '86400', false);
                $response->headers->set('Vary', 'Origin', false);
            } else {
                // Remove CORS headers if origin is not allowed
                $response->headers->remove('Access-Control-Allow-Origin');
                $response->headers->remove('Access-Control-Allow-Credentials');
            }
        }
        
        return $response;
    }
}
