<?php

namespace Bunkuris\Tests\Support;

use Bunkuris\Support\AbstractCacheKeyManager;
use Bunkuris\Contracts\CacheKeyManagerContract;
use Bunkuris\Support\CacheKey;
use Illuminate\Support\Carbon;

/**
 * @phpstan-import-type TemplateArray from CacheKeyManagerContract
 */
class ExampleCacheKeyManager extends AbstractCacheKeyManager
{
    public static function getModelProfileCacheKey(int $id): CacheKey
    {
        return static::buildCacheKey('model_profile', [
            'id' => $id,
        ]);
    }

    public static function getModelDataCacheKey(int $id): CacheKey
    {
        return static::buildCacheKey('model_data', [
            'id' => $id,
        ]);
    }

    public static function getModelListCacheKey(): CacheKey
    {
        return static::buildCacheKey('model_list');
    }

    /**
     * Returns the available templates for this cache key manager.
     * 
     * @return TemplateArray
     */
    public static function getTemplates(): array
    {
        return [
            'model_profile' => [
                'pattern' => 'example_model:{id}:profile',
                'in_working_hours_ttl' => Carbon::now()->addHour(),
                'after_working_hours_ttl' => Carbon::now()->addHours(2),
            ],
            'model_data' => [
                'pattern' => 'example_model:{id}',
                'in_working_hours_ttl' => Carbon::now()->addMinutes(30),
            ],
            'model_list' => [
                'pattern' => 'example_models:list',
                'in_working_hours_ttl' => Carbon::now()->addMinutes(10),
                'after_working_hours_ttl' => Carbon::now()->addMinutes(20),
            ],
        ];
    }
}
