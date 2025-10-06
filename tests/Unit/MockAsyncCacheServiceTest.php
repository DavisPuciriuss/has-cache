<?php

namespace Bunkuris\Tests\Unit;

use Bunkuris\Testing\MockAsyncCacheService;
use Bunkuris\Tests\TestCase;

class MockAsyncCacheServiceTest extends TestCase
{
    private MockAsyncCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MockAsyncCacheService();
    }

    public function test_delete_multiple_async_records_deleted_keys(): void
    {
        $result = $this->service->deleteMultipleAsync(['key1', 'key2', 'key3']);

        $this->assertTrue($result);
        $this->assertEquals(['key1', 'key2', 'key3'], $this->service->getDeletedKeys());
    }

    public function test_delete_multiple_async_with_custom_chunk_size(): void
    {
        $result = $this->service->deleteMultipleAsync(['key1', 'key2'], 500);

        $this->assertTrue($result);
        $this->assertEquals(['key1', 'key2'], $this->service->getDeletedKeys());
    }

    public function test_deleted_keys_accumulate(): void
    {
        $this->service->deleteMultipleAsync(['key1', 'key2']);
        $this->service->deleteMultipleAsync(['key3', 'key4']);

        $this->assertEquals(['key1', 'key2', 'key3', 'key4'], $this->service->getDeletedKeys());
    }

    public function test_get_called_methods_returns_all_calls(): void
    {
        $this->service->deleteMultipleAsync(['key1'], 1000);
        $this->service->deleteMultipleAsync(['key2'], 500);

        $calls = $this->service->getCalledMethods();

        $this->assertCount(2, $calls);
        $this->assertEquals('deleteMultipleAsync', $calls[0]['method']);
        $this->assertEquals(['keys' => ['key1'], 'chunkSize' => 1000], $calls[0]['params']);
        $this->assertEquals('deleteMultipleAsync', $calls[1]['method']);
        $this->assertEquals(['keys' => ['key2'], 'chunkSize' => 500], $calls[1]['params']);
    }

    public function test_get_called_method_filters_by_method_name(): void
    {
        $this->service->deleteMultipleAsync(['key1'], 1000);
        $this->service->deleteMultipleAsync(['key2'], 500);

        $calls = $this->service->getCalledMethod('deleteMultipleAsync');

        $this->assertCount(2, $calls);
        $this->assertEquals('deleteMultipleAsync', $calls[0]['method']);
    }

    public function test_get_called_method_returns_empty_for_nonexistent_method(): void
    {
        $this->service->deleteMultipleAsync(['key1'], 1000);

        $calls = $this->service->getCalledMethod('nonexistentMethod');

        $this->assertEmpty($calls);
    }

    public function test_clear_deleted_keys(): void
    {
        $this->service->deleteMultipleAsync(['key1', 'key2']);

        $this->assertCount(2, $this->service->getDeletedKeys());

        $this->service->clearDeletedKeys();

        $this->assertEmpty($this->service->getDeletedKeys());
    }

    public function test_clear_called_methods(): void
    {
        $this->service->deleteMultipleAsync(['key1'], 1000);
        $this->service->deleteMultipleAsync(['key2'], 500);

        $this->assertCount(2, $this->service->getCalledMethods());

        $this->service->clearCalledMethods();

        $this->assertEmpty($this->service->getCalledMethods());
    }

    public function test_reset_clears_everything(): void
    {
        $this->service->deleteMultipleAsync(['key1', 'key2'], 1000);

        $this->assertNotEmpty($this->service->getDeletedKeys());
        $this->assertNotEmpty($this->service->getCalledMethods());

        $this->service->reset();

        $this->assertEmpty($this->service->getDeletedKeys());
        $this->assertEmpty($this->service->getCalledMethods());
    }

    public function test_delete_multiple_async_with_empty_array(): void
    {
        $result = $this->service->deleteMultipleAsync([]);

        $this->assertTrue($result);
        $this->assertEmpty($this->service->getDeletedKeys());
    }
}
