<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];

//return [
//	'paths' => ['api/*', 'sanctum/csrf-cookie'],
//
//	'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
//
//
//	'allowed_origins' => env('CORS_ALLOWED_ORIGINS')
//		? explode(',', env('CORS_ALLOWED_ORIGINS'))
//		: ['*'],
//
//	'allowed_origins_patterns' => [],
//
//	'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],
//
//	'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
//
//	'max_age' => 86400, // 24 horas
//
//	'supports_credentials' => true,
//];