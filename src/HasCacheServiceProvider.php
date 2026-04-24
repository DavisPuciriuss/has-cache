<?php

namespace Bunkuris;

use Bunkuris\Console\CreateCacheKeyManagerCommand;
use Bunkuris\Console\ListCacheKeyManagersCommand;
use Bunkuris\Contracts\AsyncCacheContract;
use Bunkuris\Support\RedisAsyncCacheService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Config\Repository as ConfigRepository;

class HasCacheServiceProvider extends ServiceProvider
{
    /**
     * @var ConfigRepository
     */
    protected ConfigRepository $config;

    public function register(): void
    {
        if (!defined('BUNKURIS_PATH')) {
            define('BUNKURIS_PATH', realpath(__DIR__ . '/../')); // @codeCoverageIgnore
        }

        $this->config = $this->app->make('config');

        $this->configure();
        $this->offerPublishing();
        $this->registerServices();
        $this->registerCommands();
    }

    public function boot(): void
    {
        //
    }

    /**
     * Set up the resource publishing groups for has-cache.
     *
     * @return void
     */
    protected function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                BUNKURIS_PATH . '/config/has-cache.php' => $this->app->configPath('has-cache.php'),
            ], 'has-cache-config');
        }
    }

    /**
     * Set up the configuration for has-cache.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->mergeConfigFrom(BUNKURIS_PATH . '/config/has-cache.php', 'has-cache');
    }

    /**
     * Register the has-cache Artisan commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateCacheKeyManagerCommand::class,
                ListCacheKeyManagersCommand::class,
            ]);
        }
    }

    /**
     * Register services in the container.
     *
     * @return void
     */
    protected function registerServices(): void
    {
        $this->app->singleton(AsyncCacheContract::class, RedisAsyncCacheService::class);
    }
}