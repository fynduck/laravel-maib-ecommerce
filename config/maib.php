<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Project credentials
    |--------------------------------------------------------------------------
    |
    | Project ID, Secret and Signature Key are issued in the maibmerchants.md
    | dashboard after the project is activated. The Signature Key is used to
    | validate the notification signature received on the callback URL.
    |
    */

    'project_id'     => env('MAIB_PROJECT_ID'),
    'project_secret' => env('MAIB_PROJECT_SECRET'),
    'signature_key'  => env('MAIB_SIGNATURE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API connection
    |--------------------------------------------------------------------------
    */

    'base_url' => env('MAIB_BASE_URL', 'https://api.maibmerchants.md/v1/'),
    'timeout'  => (int) env('MAIB_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Access token caching
    |--------------------------------------------------------------------------
    |
    | The access token is cached and transparently refreshed before expiry.
    | "ttl_leeway" is the number of seconds before the real expiry at which the
    | token is considered stale (to avoid using a token that expires mid-flight).
    |
    */

    'token' => [
        'cache_store'  => env('MAIB_CACHE_STORE'),
        'cache_prefix' => 'maib_token',
        'ttl_leeway'   => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback (webhook) route
    |--------------------------------------------------------------------------
    |
    | maib sends payment notifications to your callback URL via HTTPS POST.
    | When enabled, the package registers this route guarded by the signature
    | verification middleware and dispatches events you can listen to.
    |
    */

    'callback' => [
        'enabled'    => true,
        'route'      => env('MAIB_CALLBACK_ROUTE', '/maib/callback'),
        'name'       => 'maib.callback',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional payment persistence
    |--------------------------------------------------------------------------
    |
    | When true (and the published migration has been run), incoming callbacks
    | are recorded in the maib_payments table via the MaibPayment model.
    |
    */

    'store_payments' => env('MAIB_STORE_PAYMENTS', false),

];
