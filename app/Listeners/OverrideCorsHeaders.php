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
            // Force remove wildcard and set specific origin
            $response->headers->remove('Access-Control-Allow-Origin');
            $response->headers->set('Access-Control-Allow-Origin', $origin, false);
            $response->headers->set('Access-Control-Allow-Credentials', 'true', false);
        }
    }
}

