# Cache Key Managers

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
                'pattern'                 => 'posts:{id}',
                'in_working_hours_ttl'    => 3600,   // 1 hour during active hours
                'after_working_hours_ttl' => 21600,  // 6 hours outside active hours (optional)
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
