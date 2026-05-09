<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    |
    | The Redis connection name to use for storing model data. This should
    | match a connection defined in your config/database.php redis connections.
    |
    */

    'connection' => env('REDILOQUENT_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Key Prefix
    |--------------------------------------------------------------------------
    |
    | A global prefix applied to every Redis key managed by Rediloquent.
    | This helps avoid key collisions when sharing a Redis instance.
    |
    */

    'prefix' => env('REDILOQUENT_PREFIX', 'rediloquent'),

];
