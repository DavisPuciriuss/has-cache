<?php

namespace Bunkuris\Traits;

use Bunkuris\Support\CacheKey;
use Bunkuris\Facades\AsyncCache;
use DB;
use Illuminate\Database\Eloquent\Attributes\Boot;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasCache.
 *
 * @mixin \Eloquent
 *
 * @template TModel of Model
 */
trait HasCache
{
    /**
     * @var array<int, array<string, true>>
     */
    protected static array $_originalCacheKeys = [];

    protected static bool $_hasCache_disabled = false;

    /**
     * Boot the trait.
     */
    #[Boot]
    public static function registerCacheEvents(): void
    {
        static::registerModelEvent('booted', function () {
            static::updating(function ($model) {
                if (self::$_hasCache_disabled) {
                    return;
                }

                /** @var int $key */
                $key = \spl_object_id($model);

                self::$_originalCacheKeys[$key] = $model->getOriginalCacheKeys();
            });

            static::saved(function ($model) {
                if (self::$_hasCache_disabled) {
                    return;
                }

                /** @var int $key */
                $key = \spl_object_id($model);

                $originalKeys = static::$_originalCacheKeys[$key] ?? [];

                /** @var array<string> $cacheKeys */
                $cacheKeys = array_keys(array_merge($originalKeys, $model->getCacheKeys()));

                AsyncCache::deleteMultipleAsync($cacheKeys);

                unset(static::$_originalCacheKeys[$key]);
            });

            static::deleted(function ($model) {
                if (self::$_hasCache_disabled) {
                    return;
                }

                /** @var array<string> $cacheKeys */
                $cacheKeys = array_keys($model->getCacheKeys());

                AsyncCache::deleteMultipleAsync($cacheKeys);
            });
        });
    }

    /**
     * Returns all cache keys that were valid before the model was saved.
     *
     * @return array<string, true>
     */
    public function getOriginalCacheKeys(): array
    {
        $modelClass = \get_class($this);

        /** @var TModel $originalModel */
        $originalModel = new $modelClass;
        $originalModel->setRawAttributes($this->getOriginal());

        return $originalModel->getCacheKeys();
    }

    /**
     * Returns all cache keys that should be purged after save, delete.
     *
     * @return array<string, true>
     */
    public function getCacheKeys(): array
    {
        return [];
    }

    /**
     * Temporarily disable cache purging within the given callback.
     */
    public static function withoutCachePurge(callable $callback): mixed
    {
        $disabled = static::$_hasCache_disabled;

        static::$_hasCache_disabled = true;

        try {
            return $callback();
        } finally {
            static::$_hasCache_disabled = $disabled;
        }
    }

    /**
     * Queue cache keys to be cleared after transaction commits.
     *
     * @param  array<int, CacheKey>  $cacheKeys
     */
    public static function clearCacheAfterCommit(array $cacheKeys): void
    {
        /** @var array<string> $keysToDelete */
        $keysToDelete = array_map(fn (CacheKey $key) => $key->$key, $cacheKeys);

        DB::afterCommit(function () use ($keysToDelete) {
            if (!static::$_hasCache_disabled) {
                AsyncCache::deleteMultipleAsync($keysToDelete);
            }
        });
    }
}
