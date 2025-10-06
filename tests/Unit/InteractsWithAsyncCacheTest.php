<?php

namespace Bunkuris\Tests\Unit;

use Bunkuris\Testing\InteractsWithAsyncCache;
use Bunkuris\Tests\Support\ExampleModel;
use Bunkuris\Tests\TestCase;

class InteractsWithAsyncCacheTest extends TestCase
{
    use InteractsWithAsyncCache;

    public function test_get_mock_async_cache_returns_instance(): void
    {
        $mock = $this->getMockAsyncCache();

        $this->assertInstanceOf(\Bunkuris\Testing\MockAsyncCacheService::class, $mock);
    }

    public function test_reset_async_cache_clears_data(): void
    {
        ExampleModel::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->assertNotEmpty($this->getMockAsyncCache()->getDeletedKeys());

        $this->resetAsyncCache();

        $this->assertEmpty($this->getMockAsyncCache()->getDeletedKeys());
    }

    public function test_assert_cache_key_count(): void
    {
        ExampleModel::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->assertCacheKeyCount(2);
    }

    public function test_assert_cache_key_count_fails_with_wrong_count(): void
    {
        ExampleModel::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the number of deleted cache keys is 5');

        $this->assertCacheKeyCount(5);
    }

    public function test_assert_cache_key_deleted(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->assertCacheKeyDeleted("example_model:{$model->id}");
        $this->assertCacheKeyDeleted("example_model:{$model->id}:profile");
    }

    public function test_assert_cache_key_deleted_fails_for_missing_key(): void
    {
        ExampleModel::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the cache key [non_existent_key] was deleted');

        $this->assertCacheKeyDeleted('non_existent_key');
    }

    public function test_assert_cache_keys_deleted(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->assertCacheKeysDeleted([
            "example_model:{$model->id}",
            "example_model:{$model->id}:profile",
        ]);
    }

    public function test_assert_cache_keys_deleted_fails_for_missing_keys(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the cache keys were deleted: missing_key');

        $this->assertCacheKeysDeleted([
            "example_model:{$model->id}",
            'missing_key',
        ]);
    }

    public function test_reset_async_cache_resets_cache(): void
    {
        ExampleModel::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->assertNotEmpty($this->getMockAsyncCache()->getDeletedKeys());

        $this->resetAsyncCache();

        $this->assertEmpty($this->getMockAsyncCache()->getDeletedKeys());
    }

    public function test_custom_assertion_messages(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Custom error message');

        $this->assertCacheKeyCount(5, 'Custom error message');
    }

    public function test_assert_cache_method_called_with_params(): void
    {
        $mock = $this->getMockAsyncCache();
        
        // Make an actual call that will be recorded
        $mock->deleteMultipleAsync(['key1', 'key2'], 1000);
        
        $this->assertCacheMethodCalledWithParams('deleteMultipleAsync', ['keys' => ['key1', 'key2'], 'chunkSize' => 1000]);
    }

    public function test_assert_cache_method_called_with_params_fails_when_method_not_called(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the async cache method [someMethod] was called');

        $this->assertCacheMethodCalledWithParams('someMethod', ['param1']);
    }

    public function test_assert_cache_method_called_with_params_fails_when_params_dont_match(): void
    {
        $mock = $this->getMockAsyncCache();
        
        // Make a call with different params
        $mock->deleteMultipleAsync(['key1'], 500);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the async cache method [deleteMultipleAsync] was called with the expected parameters');

        $this->assertCacheMethodCalledWithParams('deleteMultipleAsync', ['keys' => ['key2'], 'chunkSize' => 1000]);
    }

    public function test_assert_cache_method_called_with_params_custom_message(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Custom method call message');

        $this->assertCacheMethodCalledWithParams('someMethod', ['param'], 'Custom method call message');
    }

    public function test_get_mock_async_cache_throws_exception_when_not_mock(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AsyncCache mock not properly bound');

        // Temporarily bind a non-mock instance
        $this->app->singleton(\Bunkuris\Contracts\AsyncCacheContract::class, function () {
            return new class implements \Bunkuris\Contracts\AsyncCacheContract {
                public function deleteMultipleAsync(array $keys, int $chunkSize = 1000): bool
                {
                    return true;
                }
            };
        });

        try {
            $this->getMockAsyncCache();
        } finally {
            // Restore the mock for tearDown
            $this->app->singleton(\Bunkuris\Contracts\AsyncCacheContract::class, function () {
                return new \Bunkuris\Testing\MockAsyncCacheService();
            });
        }
    }
}
