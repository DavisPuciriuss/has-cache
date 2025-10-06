<?php

namespace Bunkuris\Tests\Unit;

use Bunkuris\Facades\AsyncCache;
use Bunkuris\Testing\InteractsWithAsyncCache;
use Bunkuris\Tests\Support\ExampleCacheKeyManager;
use Bunkuris\Tests\Support\ExampleModel;
use Bunkuris\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class HasCacheTest extends TestCase
{
    use InteractsWithAsyncCache;

    public function test_cache_keys_are_deleted_on_model_save(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test Model',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->assertCacheKeysDeleted([
            "example_model:{$model->id}",
            "example_model:{$model->id}:profile",
        ]);
    }

    public function test_cache_keys_are_deleted_on_model_update(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test Model',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->resetAsyncCache();

        $model->update(['name' => 'Updated Model']);

        $this->assertCacheKeysDeleted([
            "example_model:{$model->id}",
            "example_model:{$model->id}:profile",
        ]);
    }

    public function test_cache_keys_are_deleted_on_model_delete(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test Model',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->resetAsyncCache();

        $model->delete();

        $this->assertCacheKeysDeleted([
            "example_model:{$model->id}",
            "example_model:{$model->id}:profile",
        ]);
    }

    public function test_original_cache_keys_are_deleted_when_model_changes(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test Model',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $originalId = $model->id;

        $this->resetAsyncCache();

        // Simulate an ID change (edge case but should still work)
        $model->id = 999;
        $model->save();

        // Both original and new cache keys should be deleted
        $this->assertCacheKeysDeleted([
            "example_model:{$originalId}",
            "example_model:{$originalId}:profile",
            "example_model:999",
            "example_model:999:profile",
        ]);
    }

    public function test_without_cache_purge_disables_cache_deletion(): void
    {
        ExampleModel::withoutCachePurge(function () {
            ExampleModel::create([
                'name' => 'Test Model',
                'email' => 'test@example.com',
                'is_active' => true,
            ]);
        });

        $this->assertCacheKeyCount(0);
    }

    public function test_without_cache_purge_restores_previous_state(): void
    {
        ExampleModel::withoutCachePurge(function () {
            ExampleModel::create([
                'name' => 'Test Model',
                'email' => 'test@example.com',
                'is_active' => true,
            ]);
        });

        $this->resetAsyncCache();

        // Cache purging should be re-enabled
        $model = ExampleModel::create([
            'name' => 'Another Model',
            'email' => 'another@example.com',
            'is_active' => true,
        ]);

        $this->assertCacheKeysDeleted([
            "example_model:{$model->id}",
            "example_model:{$model->id}:profile",
        ]);
    }

    public function test_without_cache_purge_returns_callback_result(): void
    {
        $result = ExampleModel::withoutCachePurge(function () {
            return 'test-result';
        });

        $this->assertEquals('test-result', $result);
    }

    public function test_get_cache_keys_returns_empty_array_by_default(): void
    {
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            use \Bunkuris\Traits\HasCache;
        };

        $this->assertEquals([], $model->getCacheKeys());
    }

    public function test_get_original_cache_keys_returns_keys_with_original_attributes(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test Model',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $originalId = $model->id;

        // Change the ID
        $model->id = 999;

        $originalKeys = $model->getOriginalCacheKeys();

        // Should use original ID, not the changed one
        $this->assertArrayHasKey("example_model:{$originalId}", $originalKeys);
        $this->assertArrayHasKey("example_model:{$originalId}:profile", $originalKeys);
        $this->assertArrayNotHasKey("example_model:999", $originalKeys);
    }

    public function test_clear_cache_after_commit_queues_deletion(): void
    {
        $cacheKey1 = ExampleCacheKeyManager::getModelDataCacheKey(1);
        $cacheKey2 = ExampleCacheKeyManager::getModelProfileCacheKey(1);

        DB::transaction(function () use ($cacheKey1, $cacheKey2) {
            ExampleModel::clearCacheAfterCommit([$cacheKey1, $cacheKey2]);
        });

        $this->assertCacheKeysDeleted([
            'example_model:1',
            'example_model:1:profile',
        ]);
    }

    public function test_clear_cache_after_commit_respects_disabled_state(): void
    {
        $cacheKey1 = ExampleCacheKeyManager::getModelDataCacheKey(1);
        $cacheKey2 = ExampleCacheKeyManager::getModelProfileCacheKey(1);

        ExampleModel::withoutCachePurge(function () use ($cacheKey1, $cacheKey2) {
            DB::transaction(function () use ($cacheKey1, $cacheKey2) {
                ExampleModel::clearCacheAfterCommit([$cacheKey1, $cacheKey2]);
            });
        });

        $this->assertCacheKeyCount(0);
    }

    public function test_multiple_models_can_be_created_without_interference(): void
    {
        $model1 = ExampleModel::create([
            'name' => 'Model 1',
            'email' => 'model1@example.com',
            'is_active' => true,
        ]);

        $model2 = ExampleModel::create([
            'name' => 'Model 2',
            'email' => 'model2@example.com',
            'is_active' => true,
        ]);

        $this->assertCacheKeysDeleted([
            "example_model:{$model1->id}",
            "example_model:{$model1->id}:profile",
            "example_model:{$model2->id}",
            "example_model:{$model2->id}:profile",
        ]);
    }

    public function test_updating_event_stores_original_cache_keys(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test Model',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->resetAsyncCache();

        // Update model
        $model->name = 'Updated Model';
        $model->save();

        // Should have deleted both original and current keys
        $this->assertCacheKeyDeleted("example_model:{$model->id}");
        $this->assertCacheKeyDeleted("example_model:{$model->id}:profile");
    }

    public function test_saved_event_cleans_up_original_keys(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test Model',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->resetAsyncCache();

        // Update multiple times
        $model->update(['name' => 'Update 1']);
        $this->resetAsyncCache();

        $model->update(['name' => 'Update 2']);

        // Should only have the current model's keys, not accumulating old ones
        $this->assertCacheKeyCount(2);
    }

    public function test_cache_keys_not_deleted_on_update_when_cache_disabled(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test Model',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->resetAsyncCache();

        // Update model with cache purge disabled
        ExampleModel::withoutCachePurge(function () use ($model) {
            $model->name = 'Updated Name';
            $model->save();
        });

        // No cache keys should be deleted
        $this->assertCacheKeyCount(0);
    }

    public function test_cache_keys_not_deleted_on_delete_when_cache_disabled(): void
    {
        $model = ExampleModel::create([
            'name' => 'Test Model',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->resetAsyncCache();

        // Delete model with cache purge disabled
        ExampleModel::withoutCachePurge(function () use ($model) {
            $model->delete();
        });

        // No cache keys should be deleted
        $this->assertCacheKeyCount(0);
    }
}
