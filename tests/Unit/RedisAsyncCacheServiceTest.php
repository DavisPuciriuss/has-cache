<?php

namespace Bunkuris\Tests\Unit;

use Bunkuris\Support\RedisAsyncCacheService;
use Bunkuris\Tests\TestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as RepositoryContract;

class RedisAsyncCacheServiceTest extends TestCase
{
    public function test_delete_multiple_async_with_empty_array(): void
    {
        $cache = $this->app->make(RepositoryContract::class);
        $service = new RedisAsyncCacheService($cache);

        $result = $service->deleteMultipleAsync([]);

        $this->assertTrue($result);
    }

    public function test_delete_multiple_async_with_non_redis_store(): void
    {
        $arrayStore = new ArrayStore();
        $cache = new Repository($arrayStore);

        $cache->put('key1', 'value1', 3600);
        $cache->put('key2', 'value2', 3600);
        $cache->put('key3', 'value3', 3600);

        $this->assertTrue($cache->has('key1'));
        $this->assertTrue($cache->has('key2'));
        $this->assertTrue($cache->has('key3'));

        $service = new RedisAsyncCacheService($cache);
        $result = $service->deleteMultipleAsync(['key1', 'key2', 'key3']);

        $this->assertTrue($result);
        $this->assertFalse($cache->has('key1'));
        $this->assertFalse($cache->has('key2'));
        $this->assertFalse($cache->has('key3'));
    }

    public function test_delete_multiple_async_handles_large_key_sets(): void
    {
        $arrayStore = new ArrayStore();
        $cache = new Repository($arrayStore);

        // Create 2500 keys
        $keys = [];
        for ($i = 1; $i <= 2500; $i++) {
            $key = "key{$i}";
            $keys[] = $key;
            $cache->put($key, "value{$i}", 3600);
        }

        $service = new RedisAsyncCacheService($cache);
        $result = $service->deleteMultipleAsync($keys, 1000);

        $this->assertTrue($result);

        // Verify all keys were deleted
        foreach ($keys as $key) {
            $this->assertFalse($cache->has($key));
        }
    }

    public function test_delete_multiple_async_with_custom_chunk_size(): void
    {
        $arrayStore = new ArrayStore();
        $cache = new Repository($arrayStore);

        $keys = [];
        for ($i = 1; $i <= 500; $i++) {
            $key = "key{$i}";
            $keys[] = $key;
            $cache->put($key, "value{$i}", 3600);
        }

        $service = new RedisAsyncCacheService($cache);
        $result = $service->deleteMultipleAsync($keys, 100);

        $this->assertTrue($result);

        foreach ($keys as $key) {
            $this->assertFalse($cache->has($key));
        }
    }

    public function test_delete_multiple_async_with_single_key(): void
    {
        $arrayStore = new ArrayStore();
        $cache = new Repository($arrayStore);

        $cache->put('single_key', 'value', 3600);

        $service = new RedisAsyncCacheService($cache);
        $result = $service->deleteMultipleAsync(['single_key']);

        $this->assertTrue($result);
        $this->assertFalse($cache->has('single_key'));
    }

    public function test_delete_multiple_async_when_chunk_size_equals_key_count(): void
    {
        $arrayStore = new ArrayStore();
        $cache = new Repository($arrayStore);

        $keys = ['key1', 'key2', 'key3', 'key4', 'key5'];
        foreach ($keys as $key) {
            $cache->put($key, 'value', 3600);
        }

        $service = new RedisAsyncCacheService($cache);
        // Chunk size equals the number of keys
        $result = $service->deleteMultipleAsync($keys, 5);

        $this->assertTrue($result);

        foreach ($keys as $key) {
            $this->assertFalse($cache->has($key));
        }
    }

    public function test_delete_multiple_async_when_chunk_size_greater_than_key_count(): void
    {
        $arrayStore = new ArrayStore();
        $cache = new Repository($arrayStore);

        $keys = ['key1', 'key2', 'key3'];
        foreach ($keys as $key) {
            $cache->put($key, 'value', 3600);
        }

        $service = new RedisAsyncCacheService($cache);
        // Chunk size is greater than the number of keys
        $result = $service->deleteMultipleAsync($keys, 10);

        $this->assertTrue($result);

        foreach ($keys as $key) {
            $this->assertFalse($cache->has($key));
        }
    }

    public function test_delete_multiple_async_with_exact_multiple_of_chunk_size(): void
    {
        $arrayStore = new ArrayStore();
        $cache = new Repository($arrayStore);

        // Create exactly 1000 keys (exact multiple of chunk size)
        $keys = [];
        for ($i = 1; $i <= 1000; $i++) {
            $key = "key{$i}";
            $keys[] = $key;
            $cache->put($key, "value{$i}", 3600);
        }

        $service = new RedisAsyncCacheService($cache);
        $result = $service->deleteMultipleAsync($keys, 500);

        $this->assertTrue($result);

        foreach ($keys as $key) {
            $this->assertFalse($cache->has($key));
        }
    }

    public function test_delete_multiple_async_with_redis_store_and_chunking(): void
    {
        // Create a mock Redis connection using an anonymous class
        $unlinkCallCount = 0;
        $redisConnection = new class($unlinkCallCount) {
            private int $callCount = 0;
            
            public function __construct(private int &$expectedCalls)
            {
            }
            
            public function unlink(array $keys): int
            {
                $this->callCount++;
                $this->expectedCalls = $this->callCount;
                return count($keys);
            }
            
            public function getCallCount(): int
            {
                return $this->callCount;
            }
        };

        // Create a mock RedisStore
        $redisStore = $this->createMock(\Illuminate\Cache\RedisStore::class);
        // connection() is called once per chunk (2 times)
        $redisStore->expects($this->exactly(2))
            ->method('connection')
            ->willReturn($redisConnection);

        // Create a mock cache repository
        $cache = $this->createMock(\Illuminate\Contracts\Cache\Repository::class);
        $cache->expects($this->once())
            ->method('getStore')
            ->willReturn($redisStore);

        // Create 2000 keys to trigger chunking
        $keys = [];
        for ($i = 1; $i <= 2000; $i++) {
            $keys[] = "key{$i}";
        }

        $service = new RedisAsyncCacheService($cache);
        $result = $service->deleteMultipleAsync($keys, 1000);

        $this->assertTrue($result);
        $this->assertEquals(2, $unlinkCallCount, 'unlink should be called exactly 2 times');
    }

    public function test_delete_multiple_async_with_redis_store_without_chunking(): void
    {
        // Create a mock cache repository
        $cache = $this->createMock(\Illuminate\Contracts\Cache\Repository::class);
        
        // Create a mock RedisStore
        $redisStore = $this->createMock(\Illuminate\Cache\RedisStore::class);
        
        $cache->expects($this->once())
            ->method('getStore')
            ->willReturn($redisStore);

        // When chunk size >= key count, it should use deleteMultiple
        $cache->expects($this->once())
            ->method('deleteMultiple')
            ->with(['key1', 'key2', 'key3'])
            ->willReturn(true);

        $service = new RedisAsyncCacheService($cache);
        $result = $service->deleteMultipleAsync(['key1', 'key2', 'key3'], 10);

        $this->assertTrue($result);
    }
}


