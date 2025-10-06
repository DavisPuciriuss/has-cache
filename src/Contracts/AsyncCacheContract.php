<?php

namespace Bunkuris\Contracts;

interface AsyncCacheContract
{
    /**
     * Delete multiple cache items asynchronously.
     *
     * @param  array<string>  $keys  Array or iterable of cache keys to delete
     * @param  int<1, max>  $chunkSize  Number of keys to delete in each chunk (default: 1000)
     */
    public function deleteMultipleAsync(array $keys, int $chunkSize = 1000): bool;
}
