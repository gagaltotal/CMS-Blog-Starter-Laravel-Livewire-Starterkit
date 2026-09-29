<?php

return [

    /*
     * This app is a classic server-rendered Laravel + Livewire monolith,
     * not a separate API consumed from another origin, so CORS is left at
     * a conservative default (no cross-origin access to app routes). If
     * you later add an API meant to be called from another domain, list
     * the specific origins in `allowed_origins` — avoid '*' if the API
     * requires cookies/session auth.
     */
    'paths' => ['up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
