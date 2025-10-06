HasCache
==============

HasCache is a package that provides automatic cache invalidation for Laravel Eloquent models. It monitors model lifecycle events (create, update, delete) and automatically clears associated cache keys, preventing stale data without manual cache management. The package includes Redis-optimized async cache deletion, working hours-aware TTL management, and a type-safe cache key manager system.

## Features

- **Automatic Cache Invalidation**: Clears cache automatically when models are saved, updated, or deleted
- **Transaction-Aware**: Respects database transactions and clears cache after commit
- **Async Cache Deletion**: Non-blocking cache clearing using Redis UNLINK command
- **Working Hours TTL**: Different cache TTLs for business hours vs off-hours
- **Type-Safe Cache Keys**: PHPStan-friendly cache key managers with IDE autocomplete
- **Testing Helpers**: Built-in testing utilities to assert cache operations
- **Temporary Disable**: Can temporarily disable cache purging when needed
- **Chunk Processing**: Efficiently handles large numbers of cache keys

## Requirements

* PHP 8.2+
* Laravel 10.0+

## Installation

You can install this package as a typical composer package.

```bash
composer require bunkuris/has-cache
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=has-cache-config
```

## Basic Usage

### Step 1: Add the HasCache Trait to Your Model

```php
<?php

namespace App\Models;

use Bunkuris\Traits\HasCache;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /** @use HasCache<User> */
    use HasCache;

    public function getCacheKeys(): array
    {
        return [
            "user:{$this->id}:profile" => true,
            "user:{$this->id}:posts" => true,
            "users:list" => true,
        ];
    }
}
```

Now whenever a User model is saved, updated, or deleted, the specified cache keys will be automatically cleared.

### Step 2: Use Cache Keys in Your Application

```php
// The cache will be automatically cleared when the user is updated
$profile = Cache::remember("user:{$userId}:profile", 3600, function () use ($userId) {
    return User::find($userId)->getProfileData();
});
```

## Advanced Usage

### Using Cache Key Managers

Cache Key Managers provide a type-safe, organized way to manage cache keys with IDE autocomplete support.

#### Create a Cache Key Manager

```bash
php artisan cache:make:manager User
```

This creates a new manager at `app/Support/Cache/UserCacheKeyManager.php`:

```php
<?php

namespace App\Support\Cache;

use Bunkuris\Support\AbstractCacheKeyManager;
use Bunkuris\Support\CacheKey;

class UserCacheKeyManager extends AbstractCacheKeyManager
{
    /**
     * Get cache key for user profile data
     * 
     * @return CacheKey
     */
    public static function getProfileCacheKey(int $id): CacheKey
    {
        return static::buildCacheKey('user_profile', [
            'id' => $id,
        ]);
    }

    /**
     * Get cache key for user posts
     * 
     * @return CacheKey
     */
    public static function getPostsCacheKey(int $id): CacheKey
    {
        return static::buildCacheKey('user_posts', [
            'id' => $id,
        ]);
    }

    /**
     * Get cache key for all users list
     * 
     * @return CacheKey
     */
    public static function getUsersListCacheKey(): CacheKey
    {
        return static::buildCacheKey('user_list');
    }

    /**
     * Returns the available templates for this cache key manager.
     * 
     * @return TemplateArray
     */
    public static function getTemplates(): array
    {
        return [
            'user_profile' => [
                'pattern' => 'users:{id}:profile',
                'in_working_hours_ttl' => Carbon::now()->addHour(),
                'after_working_hours_ttl' => Carbon::now()->addHours(2),
            ],
            'user_posts' => [
                'pattern' => 'users:{id}:posts',
                'in_working_hours_ttl' => Carbon::now()->addMinutes(30),
            ],
            'user_list' => [
                'pattern' => 'users:list',
                'in_working_hours_ttl' => Carbon::now()->addMinutes(10),
                'after_working_hours_ttl' => Carbon::now()->addMinutes(20),
            ],
        ];
    }
}
```

#### Use the Cache Key Manager in Your Model

```php
<?php

namespace App\Models;

use App\Support\Cache\UserCacheKeyManager;
use Bunkuris\Traits\HasCache;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasCache;

    public function getCacheKeys(): array
    {
        return [
            (string) UserCacheKeyManager::getProfileCacheKey($this->id) => true,
            (string) UserCacheKeyManager::getPostsCacheKey($this->id) => true,
            (string) UserCacheKeyManager::getUsersListCacheKey() => true,
        ];
    }
}
```

#### Use Cache Keys Throughout Your Application

```php
use App\Support\Cache\UserCacheKeyManager;

// Remember cached data with type safety
$profile = UserCacheKeyManager::getProfileCacheKey($userId)->remember(fn() => [
    'name' => $user->name,
    'email' => $user->email,
    'avatar' => $user->avatar_url,
]);

// Check if cached
if (UserCacheKeyManager::getProfileCacheKey($userId)->cached()) {
    // Cache exists
}

// Manually forget cache
UserCacheKeyManager::getProfileCacheKey($userId)->forget();

// Put data in cache
UserCacheKeyManager::getProfileCacheKey($userId)->put($profileData);

// Get from cache (without default)
$data = UserCacheKeyManager::getProfileCacheKey($userId)->get();
```

### Working Hours TTL

By default, cache keys use different TTLs based on working hours (configurable in `config/has-cache.php`):

```php
return [
    'active_hour' => [
        'start' => 8,  // 8 AM
        'end' => 20,   // 8 PM
    ],
];
```

During working hours (8 AM - 8 PM), the default TTL is 30 minutes. Outside working hours, it's 12 hours. You can customize this per cache key:

```php
public static function getProfileCacheKey(int $userId): CacheKey
{
    return new CacheKey(
        key: "user:{$userId}:profile",
        in_active_hours_ttl: Carbon::now()->addHour(),              // 1 hour during working hours
        after_active_hours_ttl: Carbon::now()->addHours(24),        // 24 hours outside working hours
    );
}
```

Or use the same TTL regardless of time:

```php
public static function getProfileCacheKey(int $userId): CacheKey
{
    return new CacheKey(
        key: "user:{$userId}:profile",
        in_active_hours_ttl: Carbon::now()->addHour(),              // 1 hour ttl no matter the current time of day.
        after_active_hours_ttl: null,
    );
}
```

### Temporarily Disable Cache Purging

Sometimes you need to update models without clearing cache:

```php
use App\Models\User;

User::withoutCachePurge(function () {
    // These updates won't clear cache
    User::where('status', 'inactive')->update(['last_checked' => now()]);
});

// Or for a single operation
$user->withoutCachePurge(function ($user) {
    $user->increment('login_count');
});
```

### Transaction-Aware Cache Clearing

Cache is automatically cleared after database transactions commit:

```php
$cacheKey = UserCacheKeyManager::getProfileCacheKey($userId);

DB::transaction(function () use ($cacheKey) {
    $user = User::find(1);
    $user->name = 'New Name';
    $user->save();
    
    User::clearCacheAfterCommit([$cacheKey]);
});

// Cache is cleared here, after transaction commits
```

If the transaction rolls back, cache won't be cleared.

### Manual Async Cache Deletion

You can manually delete multiple cache keys asynchronously:

```php
use Bunkuris\Facades\AsyncCache;

AsyncCache::deleteMultipleAsync([
    'user:1:profile',
    'user:1:posts',
    'user:2:profile',
    'user:2:posts',
], chunkSize: 1000);
```

This uses Redis UNLINK for non-blocking deletion and processes keys in chunks for efficiency.

## Testing

The package provides testing helpers to assert cache operations in your tests.

### Setup Test Case

```php
<?php

namespace Tests;

use Bunkuris\Testing\InteractsWithAsyncCache;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use InteractsWithAsyncCache;
}
```

The `InteractsWithAsyncCache` trait automatically resets the cache state before each test, so you don't need to manually call `resetAsyncCache()` between tests.

### Assert Cache Operations

```php
use App\Models\User;
use App\Support\Cache\UserCacheKeyManager;

public function test_user_update_clears_cache(): void
{
    $user = User::factory()->create();
    
    // Update the user
    $user->update(['name' => 'New Name']);
    
    // Assert cache keys were deleted
    $this->assertCacheKeyDeleted(
        (string) UserCacheKeyManager::getProfileCacheKey($user->id)
    );
    
    // Assert multiple keys
    $this->assertCacheKeysDeleted([
        (string) UserCacheKeyManager::getProfileCacheKey($user->id),
        (string) UserCacheKeyManager::getPostsCacheKey($user->id),
    ]);
    
    // Assert exact count
    $this->assertCacheKeyCount(2);
}
```

You can still manually reset the cache state mid-test if needed:

```php
public function test_multiple_operations(): void
{
    $user = User::factory()->create();
    
    $this->assertCacheKeyCount(2);
    
    // Reset cache tracking
    $this->resetAsyncCache();
    
    $user->update(['name' => 'Updated']);
    
    $this->assertCacheKeyCount(2);
}
```

## Configuration

The configuration file `config/has-cache.php` allows you to customize working hours:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active Hours Configuration
    |--------------------------------------------------------------------------
    |
    | Define the working/active hours for your application.
    | Cache TTLs can be different during and outside these hours.
    |
    */
    'active_hour' => [
        'start' => env('CACHE_ACTIVE_HOUR_START', 8),   // 8 AM
        'end' => env('CACHE_ACTIVE_HOUR_END', 20),      // 8 PM
    ],
];
```

## How It Works

1. **Model Lifecycle Hooks**: The `HasCache` trait registers listeners for `updating`, `saved`, and `deleted` events
2. **Cache Key Collection**: Before updates, it stores the original cache keys from `getCacheKeys()`
3. **Transaction Detection**: It detects if the operation is within a database transaction
4. **Async Deletion**: After save/delete (or after transaction commit), it triggers async cache deletion
5. **Redis Optimization**: If using Redis, it uses the UNLINK command for non-blocking deletion
6. **Chunk Processing**: Large key sets are processed in chunks (default: 1000) for efficiency

## Performance Considerations

- **Redis UNLINK**: Non-blocking deletion doesn't impact application response time
- **Chunked Processing**: Large cache key sets are processed in batches
- **Transaction-Aware**: Cache clearing waits for transaction commit, preventing unnecessary work on rollbacks
- **Working Hours TTL**: Longer cache during off-hours reduces database load

## API Reference

### HasCache Trait

```php
// Get cache keys for this model instance
public function getCacheKeys(): array

// Temporarily disable cache purging
public static function withoutCachePurge(callable $callback): mixed

// Manually clear cache after transaction commit
public static function clearCacheAfterCommit(array $cacheKeys): void
```

### CacheKey Class

```php
// Create a new cache key
new CacheKey(
    string $key,
    Carbon|int $in_active_hours_ttl,
    Carbon|int|null $after_active_hours_ttl = null
)

// Cache operations
public function remember(Closure $callback): mixed
public function forget(): bool
public function put(mixed $value): void
public function get(mixed $default = null): mixed
public function cached(): bool

// Get the key as string
public function __toString(): string
```

### AsyncCache Facade

```php
// Delete multiple cache keys asynchronously
AsyncCache::deleteMultipleAsync(array $keys, int $chunkSize = 1000): bool
```

### Testing Assertions

```php
// Assert a cache key was deleted
$this->assertCacheKeyDeleted(string $key, string $message = ''): void

// Assert multiple cache keys were deleted
$this->assertCacheKeysDeleted(array $keys, string $message = ''): void

// Assert exact number of cache keys deleted
$this->assertCacheKeyCount(int $expectedCount, string $message = ''): void

// Manually reset cache tracking mid-test
$this->resetAsyncCache(): void
```

## Contributing

We'll appreciate your collaboration to this package.

When making pull requests, make sure:
* All tests are passing: `composer test`
* Test coverage is maintained at 100%: `composer test-coverage`
* There are no PHPStan errors: `composer phpstan`
* Coding standard is followed: `composer lint` or `composer fix-style` to automatically fix it

Start the development environment:
```bash
cd docker
docker-compose up -d
```

Run tests inside the container:
```bash
docker exec -it has-cache-php composer test
```

Run tests with coverage:
```bash
docker exec -it has-cache-php composer test-coverage
```

Run tests with html coverage:
```bash
docker exec -it has-cache-php composer test-coverage
```

Run tests with HTML coverage:
```bash
docker exec -it has-cache-php composer test-coverage-html
```

## License

This package is open-sourced software licensed under the MIT license.
