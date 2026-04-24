# CacheKey

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
