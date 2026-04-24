<?php

namespace Bunkuris\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Create a new cache key manager.
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
        
        if (empty($name) || !\is_string($name)) {
            $this->error('Name is required.');

            return self::FAILURE;
        }
        
        $managerName = Str::replace('CacheKeyManager', '', $name);
        $managersPath = Config::string('has-cache.managers_path', 'app/Support/Cache');
        $namespace = $this->getNamespaceFromPath($managersPath);
        $basePath = $this->laravel->basePath($managersPath);
        
        if (!File::isDirectory($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }
        
        $stubPath = BUNKURIS_PATH . '/stubs/CacheKeyManager.stub';
        
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found at: {$stubPath}");

            return self::FAILURE;
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

            return self::FAILURE;
        }
        
        File::put($filePath, $content);
        
        $this->info("Cache key manager created successfully: {$fileName}");
        $this->line("Location: {$filePath}");
        
        return self::SUCCESS;
    }

    private function getNamespaceFromPath(string $path): string
    {
        /** @var array<string> $parts */
        $parts = Str::of($path)->explode('/')->map(Str::studly(...))->toArray();

        return implode('\\', array_map('strval', $parts));
    }
}