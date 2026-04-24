# HasCache Trait

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
