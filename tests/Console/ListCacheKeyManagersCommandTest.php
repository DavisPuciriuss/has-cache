<?php

namespace Bunkuris\Tests\Console;

use Bunkuris\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ListCacheKeyManagersCommandTest extends TestCase
{
    private string $testBasePath;
    private string $managersDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testBasePath = sys_get_temp_dir() . '/has-cache-list-test-' . uniqid();
        $this->managersDir = $this->testBasePath . '/app/Support/Cache';

        $this->app->setBasePath($this->testBasePath);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testBasePath)) {
            File::deleteDirectory($this->testBasePath);
        }

        parent::tearDown();
    }

    public function test_warns_when_managers_directory_does_not_exist(): void
    {
        $this->artisan('cache:list:managers')
            ->expectsOutputToContain('No cache key managers found.')
            ->assertExitCode(0);
    }

    public function test_warns_when_no_managers_found_in_empty_directory(): void
    {
        File::makeDirectory($this->managersDir, 0755, true);

        $this->artisan('cache:list:managers')
            ->expectsOutputToContain('No cache key managers found')
            ->assertExitCode(0);
    }

    public function test_skips_non_php_files(): void
    {
        File::makeDirectory($this->managersDir, 0755, true);
        File::put($this->managersDir . '/README.txt', 'not a PHP file');

        $this->artisan('cache:list:managers')
            ->expectsOutputToContain('No cache key managers found')
            ->assertExitCode(0);
    }

    public function test_skips_php_files_with_unknown_classes(): void
    {
        File::makeDirectory($this->managersDir, 0755, true);
        File::put($this->managersDir . '/UnknownManager.php', <<<'PHP'
            <?php
            namespace App\Support\Cache\Totally\Unknown;
            class UnknownManager {}
            PHP);

        $this->artisan('cache:list:managers')
            ->expectsOutputToContain('No cache key managers found')
            ->assertExitCode(0);
    }

    public function test_skips_php_files_with_no_class_declaration(): void
    {
        File::makeDirectory($this->managersDir, 0755, true);
        File::put($this->managersDir . '/helpers.php', '<?php function myHelper() { return true; }');

        $this->artisan('cache:list:managers')
            ->expectsOutputToContain('No cache key managers found')
            ->assertExitCode(0);
    }

    public function test_skips_non_instantiable_classes(): void
    {
        // AbstractCacheKeyManager is loaded, implements the contract, but is abstract
        File::makeDirectory($this->managersDir, 0755, true);
        File::copy(
            BUNKURIS_PATH . '/src/Support/AbstractCacheKeyManager.php',
            $this->managersDir . '/AbstractCacheKeyManager.php'
        );

        $this->artisan('cache:list:managers')
            ->expectsOutputToContain('No cache key managers found')
            ->assertExitCode(0);
    }

    public function test_skips_classes_not_implementing_contract(): void
    {
        // stdClass is instantiable and always loaded, but doesn't implement CacheKeyManagerContract
        File::makeDirectory($this->managersDir, 0755, true);
        File::put($this->managersDir . '/NotAManager.php', '<?php class stdClass {}');

        $this->artisan('cache:list:managers')
            ->expectsOutputToContain('No cache key managers found')
            ->assertExitCode(0);
    }

    public function test_lists_valid_managers(): void
    {
        File::makeDirectory($this->managersDir, 0755, true);
        File::copy(
            BUNKURIS_PATH . '/tests/Support/ExampleCacheKeyManager.php',
            $this->managersDir . '/ExampleCacheKeyManager.php'
        );

        $this->artisan('cache:list:managers')
            ->expectsOutputToContain('ExampleCacheKeyManager')
            ->assertExitCode(0);
    }

    public function test_filters_managers_by_name(): void
    {
        File::makeDirectory($this->managersDir, 0755, true);
        File::copy(
            BUNKURIS_PATH . '/tests/Support/ExampleCacheKeyManager.php',
            $this->managersDir . '/ExampleCacheKeyManager.php'
        );

        $this->artisan('cache:list:managers', ['name' => 'Example'])
            ->expectsOutputToContain('ExampleCacheKeyManager')
            ->assertExitCode(0);
    }

    public function test_filter_is_case_insensitive(): void
    {
        File::makeDirectory($this->managersDir, 0755, true);
        File::copy(
            BUNKURIS_PATH . '/tests/Support/ExampleCacheKeyManager.php',
            $this->managersDir . '/ExampleCacheKeyManager.php'
        );

        $this->artisan('cache:list:managers', ['name' => 'example'])
            ->expectsOutputToContain('ExampleCacheKeyManager')
            ->assertExitCode(0);
    }

    public function test_warns_when_filter_matches_no_managers(): void
    {
        File::makeDirectory($this->managersDir, 0755, true);
        File::copy(
            BUNKURIS_PATH . '/tests/Support/ExampleCacheKeyManager.php',
            $this->managersDir . '/ExampleCacheKeyManager.php'
        );

        $this->artisan('cache:list:managers', ['name' => 'NonExistent'])
            ->expectsOutputToContain('No cache key managers found matching [NonExistent]')
            ->assertExitCode(0);
    }

    public function test_uses_custom_managers_path_from_config(): void
    {
        $customPath = 'app/Custom/Managers';
        $customDir = $this->testBasePath . '/' . $customPath;

        $this->app['config']->set('has-cache.managers_path', $customPath);

        File::makeDirectory($customDir, 0755, true);
        File::copy(
            BUNKURIS_PATH . '/tests/Support/ExampleCacheKeyManager.php',
            $customDir . '/ExampleCacheKeyManager.php'
        );

        $this->artisan('cache:list:managers')
            ->expectsOutputToContain('ExampleCacheKeyManager')
            ->assertExitCode(0);
    }
}
