<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomCors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');
        
        $allowedOrigins = [
            'https://core.kalaexcel.com',
            'https://www.kalaexcel.com',
            'https://kalaexcel.com',
        ];
        
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }
        
        if ($origin && in_array($origin, $allowedOrigins)) {
            // Force remove ALL possible CORS header variations
            $corsHeaders = [
                'Access-Control-Allow-Origin',
                'access-control-allow-origin',
                'ACCESS-CONTROL-ALLOW-ORIGIN',
            ];
            
            foreach ($corsHeaders as $header) {
                $response->headers->remove($header);
            }
            
            // Create new response with correct headers
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
