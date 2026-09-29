<?php

return [

    /*
     * The default authentication "guard" and password reset broker used
     * throughout the app unless a call explicitly overrides them.
     */
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
     * Number of seconds a fresh password confirmation lasts before actions
     * like changing an email or deleting an account require re-confirming.
     */
    'password_timeout' => 10800,

];
