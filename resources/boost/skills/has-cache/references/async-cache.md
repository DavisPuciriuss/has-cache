# AsyncCache Facade

Manual async Redis key deletion:

```php
use Bunkuris\Facades\AsyncCache;

AsyncCache::deleteMultipleAsync(['key1', 'key2', 'key3']);
AsyncCache::deleteMultipleAsync($keys, chunkSize: 500); // custom chunk size
```

Uses Redis `UNLINK` (non-blocking) when Redis store is configured; falls back to `deleteMultiple()` otherwise.
