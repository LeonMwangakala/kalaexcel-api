<?php

use Illuminate\Support\Facades\Route;

// Handle OPTIONS preflight requests BEFORE any middleware
Route::options('{any}', function () {
    $origin = request()->header('Origin');
    $allowedOrigins = ['https://core.kalaexcel.com', 'https://www.kalaexcel.com', 'https://kalaexcel.com'];
    
    if ($origin && in_array($origin, $allowedOrigins)) {
        return response('', 204)
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin')
            ->header('Access-Control-Max-Age', '86400');
    }
    
    return response('', 204);
})->where('any', '.*');

Route::get('/', function () {
    return view('welcome');
});
