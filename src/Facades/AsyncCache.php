<?php

namespace Bunkuris\Facades;

use Bunkuris\Contracts\AsyncCacheContract;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool deleteMultipleAsync(array $keys, int $chunkSize = 1000)
 */
class AsyncCache extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return AsyncCacheContract::class;
    }
}
