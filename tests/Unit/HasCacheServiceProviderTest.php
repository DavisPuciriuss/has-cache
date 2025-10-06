<?php

namespace Bunkuris\Tests\Unit;

use Bunkuris\Contracts\AsyncCacheContract;
use Bunkuris\Support\RedisAsyncCacheService;
use Bunkuris\Tests\TestCase;

class HasCacheServiceProviderTest extends TestCase
{
    public function test_async_cache_contract_is_bound(): void
    {
        $instance = $this->app->make(AsyncCacheContract::class);

        $this->assertNotNull($instance);
    }

    public function test_async_cache_contract_resolves_to_redis_service(): void
    {
        // Create a fresh app without test bindings
        $app = $this->createApplication();
        
        $provider = new \Bunkuris\HasCacheServiceProvider($app);
        $provider->register();

        $instance = $app->make(AsyncCacheContract::class);

        $this->assertInstanceOf(RedisAsyncCacheService::class, $instance);
    }

    public function test_async_cache_contract_is_singleton(): void
    {
        $instance1 = $this->app->make(AsyncCacheContract::class);
        $instance2 = $this->app->make(AsyncCacheContract::class);

        $this->assertSame($instance1, $instance2);
    }

    public function test_bunkuris_path_constant_is_defined(): void
    {
        $this->assertTrue(defined('BUNKURIS_PATH'));
    }

    public function test_config_is_merged(): void
    {
        $startHour = config('has-cache.active_hour.start');
        $endHour = config('has-cache.active_hour.end');

        $this->assertIsInt($startHour);
        $this->assertIsInt($endHour);
        $this->assertEquals(8, $startHour);
        $this->assertEquals(20, $endHour);
    }

    public function test_commands_are_registered_in_console(): void
    {
        // Make sure we're simulating console context
        $this->app['env'] = 'testing';
        
        $commands = $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all();
        
        $this->assertArrayHasKey('cache:make:manager', $commands);
    }

    public function test_bunkuris_path_constant_not_redefined_if_exists(): void
    {
        // BUNKURIS_PATH should already be defined
        $this->assertTrue(defined('BUNKURIS_PATH'));
        
        $originalPath = BUNKURIS_PATH;
        
        // Create a new provider instance and register (it shouldn't redefine the constant)
        $app = $this->createApplication();
        $provider = new \Bunkuris\HasCacheServiceProvider($app);
        $provider->register();
        
        // The path should remain the same
        $this->assertEquals($originalPath, BUNKURIS_PATH);
    }

    public function test_bunkuris_path_constant_is_defined_correctly(): void
    {
        // Test that BUNKURIS_PATH points to the package root
        $this->assertTrue(defined('BUNKURIS_PATH'));
        $this->assertDirectoryExists(BUNKURIS_PATH);
        $this->assertFileExists(BUNKURIS_PATH . '/composer.json');
        $this->assertDirectoryExists(BUNKURIS_PATH . '/src');
        $this->assertDirectoryExists(BUNKURIS_PATH . '/config');
        $this->assertDirectoryExists(BUNKURIS_PATH . '/stubs');
    }

    public function test_service_provider_boot_method_exists(): void
    {
        $app = $this->createApplication();
        $provider = new \Bunkuris\HasCacheServiceProvider($app);
        
        // Boot method should not throw an error
        $provider->boot();
        
        $this->assertTrue(true);
    }
}
