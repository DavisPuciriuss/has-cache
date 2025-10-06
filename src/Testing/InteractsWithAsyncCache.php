<?php

namespace Bunkuris\Testing;

use Bunkuris\Contracts\AsyncCacheContract;

trait InteractsWithAsyncCache
{
    protected function getMockAsyncCache(): MockAsyncCacheService
    {
        $instance = $this->app->make(AsyncCacheContract::class);

        if (!$instance instanceof MockAsyncCacheService) {
            throw new \RuntimeException('AsyncCache mock not properly bound');
        }

        return $instance;
    }

    public function resetAsyncCache(): void
    {
        $this->getMockAsyncCache()->reset();
    }

    public function tearDownInteractsAsyncCacheTrait(): void
    {
        $this->resetAsyncCache();
    }

    protected function assertCacheKeyCount(int $expectedCount, string $message = ''): void
    {
        $deletedKeys = $this->getMockAsyncCache()->getDeletedKeys();

        $actualCount = \count($deletedKeys);

        $this->assertSame(
            $expectedCount,
            $actualCount,
            $message ?: "Failed asserting that the number of deleted cache keys is {$expectedCount}. Actual count: {$actualCount}. Deleted keys: " . implode(', ', $deletedKeys)
        );
    }

    protected function assertCacheKeyDeleted(string $key, string $message = ''): void
    {
        $deletedKeys = $this->getMockAsyncCache()->getDeletedKeys();

        $this->assertContains(
            $key,
            $deletedKeys,
            $message ?: "Failed asserting that the cache key [{$key}] was deleted. Deleted keys: " . implode(', ', $deletedKeys)
        );
    }

    protected function assertCacheKeysDeleted(array $keys, string $message = ''): void
    {
        $deletedKeys = $this->getMockAsyncCache()->getDeletedKeys();

        $missingKeys = array_filter($keys, fn ($key) => !in_array($key, $deletedKeys, true));

        $this->assertEmpty(
            $missingKeys,
            $message ?: 'Failed asserting that the cache keys were deleted: ' . implode(', ', $missingKeys) . '. Deleted keys: ' . implode(', ', $deletedKeys)
        );
    }

    protected function assertCacheMethodCalledWithParams(string $method, array $params, string $message = ''): void
    {
        $calls = $this->getMockAsyncCache()->getCalledMethod($method);

        $this->assertNotEmpty(
            $calls,
            $message ?: "Failed asserting that the async cache method [{$method}] was called. No calls were made."
        );

        $found = false;

        foreach ($calls as $call) {
            if ($call === $params) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            $message ?: "Failed asserting that the async cache method [{$method}] was called with the expected parameters: " . json_encode($params) . '. Actual calls: ' . json_encode($calls)
        );
    }
}
