<?php

namespace Bunkuris\Tests\Console;

use Bunkuris\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Mockery;

class CreateCacheKeyManagerCommandTest extends TestCase
{
    private string $testBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testBasePath = sys_get_temp_dir() . '/has-cache-test-' . uniqid();
        
        $this->app->setBasePath($this->testBasePath);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testBasePath)) {
            File::deleteDirectory($this->testBasePath);
        }

        parent::tearDown();
    }

    public function test_command_creates_cache_key_manager(): void
    {
        $this->artisan('cache:make:manager', ['name' => 'User'])
            ->expectsOutput('Cache key manager created successfully: UserCacheKeyManager.php')
            ->assertExitCode(0);

        $filePath = $this->testBasePath . '/app/Support/Cache/UserCacheKeyManager.php';
        $this->assertTrue(File::exists($filePath));

        $content = File::get($filePath);
        $this->assertStringContainsString('namespace App\\Support\\Cache;', $content);
        $this->assertStringContainsString('class UserCacheKeyManager extends', $content);
    }

    public function test_command_creates_directory_if_not_exists(): void
    {
        $this->assertFalse(File::isDirectory($this->testBasePath . '/app/Support/Cache'));

        $this->artisan('cache:make:manager', ['name' => 'Product'])
            ->assertExitCode(0);

        $this->assertTrue(File::isDirectory($this->testBasePath . '/app/Support/Cache'));
    }

    public function test_command_prompts_for_name_if_not_provided(): void
    {
        $this->artisan('cache:make:manager')
            ->expectsQuestion('What is the name of the cache key manager?', 'Order')
            ->expectsOutput('Cache key manager created successfully: OrderCacheKeyManager.php')
            ->assertExitCode(0);

        $filePath = $this->testBasePath . '/app/Support/Cache/OrderCacheKeyManager.php';
        $this->assertTrue(File::exists($filePath));
    }

    public function test_command_fails_if_name_is_empty(): void
    {
        $this->artisan('cache:make:manager')
            ->expectsQuestion('What is the name of the cache key manager?', '')
            ->expectsOutput('Name is required.')
            ->assertExitCode(1);
    }

    public function test_command_strips_cache_key_manager_suffix(): void
    {
        $this->artisan('cache:make:manager', ['name' => 'ProductCacheKeyManager'])
            ->assertExitCode(0);

        $filePath = $this->testBasePath . '/app/Support/Cache/ProductCacheKeyManager.php';
        $this->assertTrue(File::exists($filePath));

        $content = File::get($filePath);
        $this->assertStringContainsString('class ProductCacheKeyManager extends', $content);
        $this->assertStringNotContainsString('ProductCacheKeyManagerCacheKeyManager', $content);
    }

    public function test_command_fails_if_file_already_exists(): void
    {
        // Create the file first
        $this->artisan('cache:make:manager', ['name' => 'Customer'])
            ->assertExitCode(0);

        // Try to create it again
        $this->artisan('cache:make:manager', ['name' => 'Customer'])
            ->expectsOutput('Cache key manager already exists: CustomerCacheKeyManager.php')
            ->assertExitCode(1);
    }

    public function test_generated_file_has_correct_structure(): void
    {
        $this->artisan('cache:make:manager', ['name' => 'Invoice'])
            ->assertExitCode(0);

        $filePath = $this->testBasePath . '/app/Support/Cache/InvoiceCacheKeyManager.php';
        $content = File::get($filePath);

        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('namespace App\\Support\\Cache;', $content);
        $this->assertStringContainsString('use Bunkuris\\Support\\AbstractCacheKeyManager;', $content);
        $this->assertStringContainsString('use Bunkuris\\Contracts\\CacheKeyManagerContract;', $content);
        $this->assertStringContainsString('class InvoiceCacheKeyManager extends AbstractCacheKeyManager', $content);
        $this->assertStringContainsString('public static function getTemplates(): array', $content);
        $this->assertStringContainsString('return [', $content);
    }

    public function test_command_shows_file_location(): void
    {
        $expectedPath = $this->testBasePath . '/app/Support/Cache/PaymentCacheKeyManager.php';

        $this->artisan('cache:make:manager', ['name' => 'Payment'])
            ->expectsOutputToContain('Location: ' . $expectedPath)
            ->assertExitCode(0);
    }

    public function test_command_fails_if_stub_missing(): void
    {
        $stubPath = BUNKURIS_PATH . '/stubs/CacheKeyManager.stub';
        $backupPath = BUNKURIS_PATH . '/stubs/CacheKeyManager.stub.backup';

        // Temporarily rename the stub file to simulate it being missing
        if (File::exists($stubPath)) {
            File::move($stubPath, $backupPath);
        }

        try {
            $this->artisan('cache:make:manager', ['name' => 'Invoice'])
                ->expectsOutput("Stub file not found at: {$stubPath}")
                ->assertExitCode(1);
        } finally {
            // Restore the stub file
            if (File::exists($backupPath)) {
                File::move($backupPath, $stubPath);
            }
        }
    }
}
