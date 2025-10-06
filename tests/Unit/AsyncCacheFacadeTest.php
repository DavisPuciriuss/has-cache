<?php

namespace Bunkuris\Tests\Unit;

use Bunkuris\Facades\AsyncCache;
use Bunkuris\Tests\Support\ExampleModel;
use Bunkuris\Tests\TestCase;
use Bunkuris\Testing\InteractsWithAsyncCache;

class AsyncCacheFacadeTest extends TestCase
{
    use InteractsWithAsyncCache;

    public function test_facade_resolves_async_cache_contract(): void
    {
        $instance = AsyncCache::getFacadeRoot();

        $this->assertInstanceOf(\Bunkuris\Contracts\AsyncCacheContract::class, $instance);
    }

    public function test_facade_delete_multiple_async(): void
    {
        $result = AsyncCache::deleteMultipleAsync(['key1', 'key2', 'key3']);

        $this->assertTrue($result);
        $this->assertCacheKeysDeleted(['key1', 'key2', 'key3']);
    }

    public function test_facade_delete_multiple_async_with_custom_chunk_size(): void
    {
        $keys = array_map(fn ($i) => "key{$i}", range(1, 100));

        $result = AsyncCache::deleteMultipleAsync($keys, 50);

        $this->assertTrue($result);
        $this->assertCacheKeyCount(100);
    }

    public function test_facade_is_used_by_model_events(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        // The facade should have been called via the trait
        $this->assertCacheKeysDeleted([
            "example_model:{$model->id}",
            "example_model:{$model->id}:profile",
        ]);
    }
}
