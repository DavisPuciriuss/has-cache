# Testing

Add `InteractsWithAsyncCache` to your test class. It auto-resets between tests and swaps in `MockAsyncCacheService`.

```php
use Bunkuris\Testing\InteractsWithAsyncCache;

class PostTest extends TestCase
{
    use InteractsWithAsyncCache;

    public function test_cache_cleared_on_save(): void
    {
        $post = Post::factory()->create();
        $post->update(['title' => 'Updated']);

        $this->assertCacheKeyDeleted("posts:{$post->id}");
        $this->assertCacheKeyDeleted('posts:list');
        $this->assertCacheKeyCount(2);
    }

    public function test_cache_cleared_on_delete(): void
    {
        $post = Post::factory()->create();
        $post->delete();

        $this->assertCacheKeyDeleted("posts:{$post->id}");
    }
}
```

**Available assertions:**
- `assertCacheKeyCount(int $count)` — exact number of keys deleted
- `assertCacheKeyDeleted(string $key)` — specific key was deleted
- `assertCacheKeysDeleted(array $keys)` — all listed keys were deleted
- `assertCacheMethodCalledWithParams(string $method, array $params)` — low-level method call assertion
