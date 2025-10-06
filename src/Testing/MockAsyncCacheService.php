<?php

namespace Bunkuris\Testing;

use Bunkuris\Contracts\AsyncCacheContract;

class MockAsyncCacheService implements AsyncCacheContract
{
    private array $deletedKeys = [];

    private array $calledMethods = [];

    /**
     * {@inheritDoc}
     */
    public function deleteMultipleAsync(array $keys, int $chunkSize = 1000): bool
    {
        $this->recordMethodCall('deleteMultipleAsync', ['keys' => $keys, 'chunkSize' => $chunkSize]);
        $this->deletedKeys = array_merge($this->deletedKeys, $keys);

        return true;
    }

    public function getDeletedKeys(): array
    {
        return $this->deletedKeys;
    }

    public function getCalledMethods(): array
    {
        return $this->calledMethods;
    }

    public function getCalledMethod(string $methodName): array
    {
        return array_filter($this->calledMethods, fn ($call) => $call['method'] === $methodName);
    }

    public function clearDeletedKeys(): void
    {
        $this->deletedKeys = [];
    }

    public function clearCalledMethods(): void
    {
        $this->calledMethods = [];
    }

    public function reset(): void
    {
        $this->clearDeletedKeys();
        $this->clearCalledMethods();
    }

    private function recordMethodCall(string $methodName, array $params): void
    {
        $this->calledMethods[] = [
            'method' => $methodName,
            'params' => $params,
        ];
    }
}
