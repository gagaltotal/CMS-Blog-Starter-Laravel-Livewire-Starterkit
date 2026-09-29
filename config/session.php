<?php

use Illuminate\Support\Str;

return [

    'driver' => env('SESSION_DRIVER', 'database'),

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    'encrypt' => env('SESSION_ENCRYPT', false),

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    'path' => env('SESSION_PATH', '/'),

    'domain' => env('SESSION_DOMAIN'),

    /*
     * Only send the session cookie over HTTPS. Keep this false for local
     * http:// development, but set SESSION_SECURE_COOKIE=true as soon as
     * you deploy behind HTTPS anywhere.
     */
    'secure' => env('SESSION_SECURE_COOKIE', false),

    /*
     * Prevents JavaScript from reading the session cookie, which is the
     * single biggest mitigation against session-hijacking via XSS. Leave
     * this true.
     */
    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
     * 'lax' stops the session cookie being sent on cross-site requests
     * except top-level navigation, which is a solid default CSRF mitigation
     * without breaking normal links into the site. Laravel's own CSRF
     * token check remains the primary defense on top of this.
     */
    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
