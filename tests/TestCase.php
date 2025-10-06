<?php

namespace Bunkuris\Tests;

use Bunkuris\Contracts\AsyncCacheContract;
use Bunkuris\Testing\MockAsyncCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Define BUNKURIS_PATH if not already defined
        if (!defined('BUNKURIS_PATH')) {
            define('BUNKURIS_PATH', realpath(__DIR__ . '/..'));
        }

        parent::setUp();

        // Bind mock async cache service
        $this->app->singleton(AsyncCacheContract::class, MockAsyncCacheService::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Bunkuris\HasCacheServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup cache to use array driver
        $app['config']->set('cache.default', 'array');

        // Setup has-cache config
        $app['config']->set('has-cache.active_hour.start', 8);
        $app['config']->set('has-cache.active_hour.end', 20);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
