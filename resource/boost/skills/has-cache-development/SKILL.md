---
name: has-cache-development
description: Build and work with HasCache features for automatic model cache invalidation, type-safe cache key managers, and working-hours-aware TTLs.
---

# HasCache Development

## When to use this skill

Use this skill when:
- Adding automatic cache invalidation to Eloquent models via the `HasCache` trait
- Creating or working with cache key managers (`cache:make:manager`, `cache:list:managers`)
- Defining cache key templates with working-hours-aware TTLs
- Testing cache behavior with `InteractsWithAsyncCache`
- Configuring `has-cache` settings (managers path, active hours)

## Installation & Configuration

Publish config:

```bash
php artisan vendor:publish --tag=has-cache-config
```

Config file (`config/has-cache.php`):

```php
return [
    'active_hour' => [
        'start' => 8,   // 8 AM
        'end'   => 20,  // 8 PM
    ],
    'managers_path' => 'app/Support/Cache', // where managers live; namespace derived automatically
];
```

## HasCache Trait

Add to any Eloquent model. Override `getCacheKeys()` to return keys to invalidate on save/delete.

```php
use Bunkuris\Traits\HasCache;

class Post extends Model
{
    use HasCache;

    public function getCacheKeys(): array
    {
        return [
            PostCacheKeyManager::buildCacheKey('post', ['id' => $this->id])->key => true,
            PostCacheKeyManager::buildCacheKey('post-list')->key                  => true,
        ];
    }
}
```

**Lifecycle behavior:**
- `updating` — captures original cache keys before attributes change
- `saved` — deletes original + current cache keys (handles slug/FK changes)
- `deleted` — deletes current cache keys
- All deletions are transaction-aware via `DB::afterCommit()`

**Disable cache purging for bulk ops:**

```php
Post::withoutCachePurge(function () {
    Post::query()->update(['published' => true]);
});
```

**Manual post-commit invalidation:**

```php
Post::clearCacheAfterCommit([
    PostCacheKeyManager::buildCacheKey('post', ['id' => $this->id]),
]);
```

## CacheKey

Wraps a cache key string with TTL-aware operations.

```php
use Bunkuris\Support\CacheKey;

// Single TTL (always)
$key = new CacheKey('posts:featured', 3600);

// Working-hours-aware TTL: 1 hour during active hours, 6 hours outside
$key = new CacheKey('posts:featured', 3600, 21600);

// Operations
$value = $key->remember(fn () => Post::featured()->get());
$key->put($value);
$value = $key->get(default: []);
$exists = $key->cached();
$key->forget();
(string) $key; // returns key string
```

TTL accepts `int` (seconds) or `Carbon` instance. Active hours configured in `has-cache.active_hour`.

## Cache Key Managers

Generate a new manager:

```bash
php artisan cache:make:manager Post
# Creates: app/Support/Cache/PostCacheKeyManager.php
# Namespace: App\Support\Cache
```

List all managers:

```bash
php artisan cache:list:managers
php artisan cache:list:managers Post  # filter by name
```

Define templates in the generated manager:

```php
namespace App\Support\Cache;

use Bunkuris\Support\AbstractCacheKeyManager;
use Bunkuris\Contracts\CacheKeyManagerContract;

/** @phpstan-import-type TemplateArray from CacheKeyManagerContract */
class PostCacheKeyManager extends AbstractCacheKeyManager
{
    /** @return TemplateArray */
    public static function getTemplates(): array
    {
        return [
            'post' => [
                'pattern'               => 'posts:{id}',
                'in_working_hours_ttl'  => 3600,        // 1 hour during active hours
                'after_working_hours_ttl' => 21600,     // 6 hours outside active hours (optional)
            ],
            'post-list' => [
                'pattern'              => 'posts:list',
                'in_working_hours_ttl' => 300,
            ],
        ];
    }
}
```

Build keys from templates:

```php
// Template with params — replaces {id} placeholder
$key = PostCacheKeyManager::buildCacheKey('post', ['id' => 42]);

// Template without params
$key = PostCacheKeyManager::buildCacheKey('post-list');

// Use the key
$posts = $key->remember(fn () => Post::paginate());
```

**Template format:**
- `pattern` — key string; use `{paramName}` for interpolated values
- `in_working_hours_ttl` — `int` (seconds) or `Carbon`; used during active hours and when no `after_working_hours_ttl`
- `after_working_hours_ttl` — optional; used outside active hours

## Testing

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

## Facade

`AsyncCache` facade for manual async Redis key deletion:

```php
use Bunkuris\Facades\AsyncCache;

AsyncCache::deleteMultipleAsync(['key1', 'key2', 'key3']);
AsyncCache::deleteMultipleAsync($keys, chunkSize: 500); // custom chunk size
```

Uses Redis `UNLINK` (non-blocking) when Redis store is configured; falls back to `deleteMultiple()` otherwise.
