<?php

namespace Tests;

use Digitlimit\Rediloquent\RediloquentServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Flush the test Redis database before every test so each test starts clean.
        $this->redisConnection()->flushDb();
    }

    protected function tearDown(): void
    {
        $this->redisConnection()->flushDb();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            RediloquentServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Point Rediloquent at a dedicated Redis database (9) to avoid
        // collisions with any application data during testing.
        $app['config']->set('database.redis.default', [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'port'     => (int) env('REDIS_PORT', 6379),
            'database' => (int) env('REDIS_DB', 9),
            'password' => env('REDIS_PASSWORD', null),
        ]);

        $app['config']->set('rediloquent.connection', 'default');
        $app['config']->set('rediloquent.prefix', 'test');
    }

    private function redisConnection(): \Illuminate\Redis\Connections\Connection
    {
        return $this->app['redis']->connection('default');
    }
}
