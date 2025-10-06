<?php

namespace Bunkuris\Support;

use Bunkuris\Contracts\AsyncCacheContract;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Repository;

class RedisAsyncCacheService implements AsyncCacheContract
{
    public function __construct(
        private Repository $cache
    ) {}

    /**
     * {@inheritDoc}
     */
    public function deleteMultipleAsync(array $keys, int $chunkSize = 1000): bool
    {
        if (!count($keys)) {
            return true;
        }

        $store = $this->cache->getStore();

        if (!$store instanceof RedisStore) {
            return $this->cache->deleteMultiple($keys);
        }

        if ($chunkSize >= count($keys)) {
            return $this->cache->deleteMultiple($keys);
        }

        $chunks = array_chunk($keys, $chunkSize);

        foreach ($chunks as $chunk) {
            $store->connection()->unlink($chunk);
        }

        return true;
    }
}
