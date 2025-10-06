<?php

namespace Bunkuris\Tests\Support;

use Bunkuris\Traits\HasCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Example model for testing.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $description
 * @property bool $is_active
 * 
 * @mixin \Eloquent
 */
class ExampleModel extends Model
{
    /** @use HasCache<ExampleModel> */
    use HasCache;

    protected $fillable = [
        'name',
        'email',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getCacheKeys(): array
    {
        $keys = [];

        $keys[(string) ExampleCacheKeyManager::getModelDataCacheKey($this->id)] = true;
        $keys[(string) ExampleCacheKeyManager::getModelProfileCacheKey($this->id)] = true;
        
        return $keys;
    }
}