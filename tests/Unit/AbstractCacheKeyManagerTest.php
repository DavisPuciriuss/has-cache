<?php

namespace Bunkuris\Tests\Unit;

use Bunkuris\Support\CacheKey;
use Bunkuris\Tests\Support\ExampleCacheKeyManager;
use Bunkuris\Tests\TestCase;
use Illuminate\Support\Carbon;

class AbstractCacheKeyManagerTest extends TestCase
{
    public function test_build_cache_key_replaces_parameters(): void
    {
        $cacheKey = ExampleCacheKeyManager::buildCacheKey('model_data', ['id' => 123]);

        $this->assertInstanceOf(CacheKey::class, $cacheKey);
        $this->assertEquals('example_model:123', $cacheKey->key);
    }

    public function test_build_cache_key_with_multiple_parameters(): void
    {
        $cacheKey = ExampleCacheKeyManager::buildCacheKey('model_profile', [
            'id' => 456,
        ]);

        $this->assertEquals('example_model:456:profile', $cacheKey->key);
    }

    public function test_build_cache_key_without_parameters(): void
    {
        $cacheKey = ExampleCacheKeyManager::buildCacheKey('model_list');

        $this->assertEquals('example_models:list', $cacheKey->key);
    }

    public function test_build_cache_key_throws_exception_for_missing_parameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing parameters for cache key template 'model_data'");

        ExampleCacheKeyManager::buildCacheKey('model_data', []);
    }

    public function test_build_cache_key_throws_exception_for_unknown_template(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Template 'unknown_template' not found");

        ExampleCacheKeyManager::buildCacheKey('unknown_template');
    }

    public function test_build_cache_key_sets_working_hours_ttl(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(10, 0, 0));

        $cacheKey = ExampleCacheKeyManager::buildCacheKey('model_profile', ['id' => 1]);

        $expectedTtl = Carbon::now()->addHour();
        $this->assertEqualsWithDelta(
            $expectedTtl->timestamp,
            $cacheKey->ttl->timestamp,
            2
        );

        Carbon::setTestNow();
    }

    public function test_build_cache_key_sets_after_hours_ttl(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(22, 0, 0));

        $cacheKey = ExampleCacheKeyManager::buildCacheKey('model_profile', ['id' => 1]);

        $expectedTtl = Carbon::now()->addHours(2);
        $this->assertEqualsWithDelta(
            $expectedTtl->timestamp,
            $cacheKey->ttl->timestamp,
            2
        );

        Carbon::setTestNow();
    }

    public function test_build_cache_key_without_after_hours_ttl(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(22, 0, 0));

        $cacheKey = ExampleCacheKeyManager::buildCacheKey('model_data', ['id' => 1]);

        $expectedTtl = Carbon::now()->addMinutes(30);
        $this->assertEqualsWithDelta(
            $expectedTtl->timestamp,
            $cacheKey->ttl->timestamp,
            2
        );

        Carbon::setTestNow();
    }

    public function test_get_template_returns_template_data(): void
    {
        $template = ExampleCacheKeyManager::getTemplate('model_data');

        $this->assertIsArray($template);
        $this->assertEquals('example_model:{id}', $template['pattern']);
        $this->assertInstanceOf(Carbon::class, $template['in_working_hours_ttl']);
        $this->assertNull($template['after_working_hours_ttl']);
    }

    public function test_get_template_includes_after_hours_ttl(): void
    {
        $template = ExampleCacheKeyManager::getTemplate('model_profile');

        $this->assertIsArray($template);
        $this->assertEquals('example_model:{id}:profile', $template['pattern']);
        $this->assertInstanceOf(Carbon::class, $template['in_working_hours_ttl']);
        $this->assertInstanceOf(Carbon::class, $template['after_working_hours_ttl']);
    }

    public function test_get_template_throws_exception_for_unknown_template(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Template 'non_existent' not found");

        ExampleCacheKeyManager::getTemplate('non_existent');
    }

    public function test_get_templates_returns_all_templates(): void
    {
        $templates = ExampleCacheKeyManager::getTemplates();

        $this->assertIsArray($templates);
        $this->assertArrayHasKey('model_profile', $templates);
        $this->assertArrayHasKey('model_data', $templates);
        $this->assertArrayHasKey('model_list', $templates);
    }

    public function test_helper_methods_return_correct_cache_keys(): void
    {
        $profileKey = ExampleCacheKeyManager::getModelProfileCacheKey(123);
        $dataKey = ExampleCacheKeyManager::getModelDataCacheKey(123);
        $listKey = ExampleCacheKeyManager::getModelListCacheKey();

        $this->assertEquals('example_model:123:profile', $profileKey->key);
        $this->assertEquals('example_model:123', $dataKey->key);
        $this->assertEquals('example_models:list', $listKey->key);
    }

    public function test_build_cache_key_handles_string_parameter_values(): void
    {
        $cacheKey = ExampleCacheKeyManager::buildCacheKey('model_data', ['id' => 'string-id']);

        $this->assertEquals('example_model:string-id', $cacheKey->key);
    }

    public function test_build_cache_key_handles_numeric_string_parameters(): void
    {
        $cacheKey = ExampleCacheKeyManager::buildCacheKey('model_data', ['id' => '999']);

        $this->assertEquals('example_model:999', $cacheKey->key);
    }
}
