<?php

namespace Bunkuris\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Create a new cache key manager.
 *
 * @codeCoverageIgnore
 */
class CreateCacheKeyManagerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:make:manager {name? : The name of the cache key manager}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new cache key manager.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name') ?: $this->ask('What is the name of the cache key manager?');
        
        if (empty($name) || !is_string($name)) {
            $this->error('Name is required.');

            return 1;
        }
        
        $managerName = str_replace('CacheKeyManager', '', $name);
        
        $namespace = 'App\\Support\\Cache';
        $basePath = app_path('Support/Cache');
        
        if (!File::isDirectory($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }
        
        $stubPath = BUNKURIS_PATH . 'stubs/CacheKeyManager.stub';
        
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found at: {$stubPath}");

            return 1;
        }
        
        $stub = File::get($stubPath);
        
        $content = str_replace(
            ['{{ managerNamespace }}', '{{ managerName }}'],
            [$namespace, $managerName],
            $stub
        );
        
        $fileName = "{$managerName}CacheKeyManager.php";
        $filePath = "{$basePath}/{$fileName}";
        
        if (File::exists($filePath)) {
            $this->error("Cache key manager already exists: {$fileName}");

            return 1;
        }
        
        File::put($filePath, $content);
        
        $this->info("Cache key manager created successfully: {$fileName}");
        $this->line("Location: {$filePath}");
        
        return 0;
    }
}