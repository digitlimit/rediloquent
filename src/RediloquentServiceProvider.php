<?php

namespace Digitlimit\Rediloquent;

use Illuminate\Support\ServiceProvider;

class RediloquentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/rediloquent.php',
            'rediloquent'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/rediloquent.php' => config_path('rediloquent.php'),
            ], 'rediloquent-config');
        }
    }
}
