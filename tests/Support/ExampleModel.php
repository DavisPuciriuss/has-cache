<?php

namespace Bunkuris\Tests\Support;

use Bunkuris\Traits\HasCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Example model for testing.
 * 
 * @mixin \Eloquent
 */
class ExampleModel extends Model
{
    /** @use HasCache<ExampleModel> */
    use HasCache;

    public function getCacheKeys(): array
    {
        return [
            'example_key' => true,
        ];
    }
}