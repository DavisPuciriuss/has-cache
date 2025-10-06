<?php

namespace Bunkuris\Tests\Unit;

use Bunkuris\Support\CacheKey;
use Bunkuris\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CacheKeyTest extends TestCase
{
    public function test_cache_key_can_be_created_with_integer_ttl(): void
    {
        $key = new CacheKey('test:key', 3600);

        $this->assertEquals('test:key', $key->key);
        $this->assertInstanceOf(Carbon::class, $key->ttl);
        
        // TTL should be approximately 3600 seconds in the future
        $secondsUntilTtl = Carbon::now()->diffInSeconds($key->ttl, false);
        $this->assertGreaterThanOrEqual(3595, $secondsUntilTtl);
        $this->assertLessThanOrEqual(3605, $secondsUntilTtl);
    }

    public function test_cache_key_can_be_created_with_carbon_ttl(): void
    {
        $ttl = Carbon::now()->addHour();
        $key = new CacheKey('test:key', $ttl);

        $this->assertEquals('test:key', $key->key);
        $this->assertEquals($ttl, $key->ttl);
    }

    public function test_cache_key_uses_working_hours_ttl_during_working_hours(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(10, 0, 0));

        $key = new CacheKey('test:key', 3600, 7200);

        $this->assertEquals('test:key', $key->key);
        $this->assertEqualsWithDelta(3600, Carbon::now()->diffInSeconds($key->ttl, false), 2);

        Carbon::setTestNow();
    }

    public function test_cache_key_uses_after_hours_ttl_outside_working_hours(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(22, 0, 0));

        $key = new CacheKey('test:key', 3600, 7200);

        $this->assertEquals('test:key', $key->key);
        $this->assertEqualsWithDelta(7200, Carbon::now()->diffInSeconds($key->ttl, false), 2);

        Carbon::setTestNow();
    }

    public function test_cache_key_uses_working_hours_ttl_at_start_boundary(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(8, 0, 0));

        $key = new CacheKey('test:key', 3600, 7200);

        $this->assertEqualsWithDelta(3600, Carbon::now()->diffInSeconds($key->ttl, false), 2);

        Carbon::setTestNow();
    }

    public function test_cache_key_uses_after_hours_ttl_at_end_boundary(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(20, 0, 0));

        $key = new CacheKey('test:key', 3600, 7200);

        // At boundary (20:00), isBetween is inclusive, so it's still working hours
        $secondsUntilTtl = Carbon::now()->diffInSeconds($key->ttl, false);
        $this->assertEqualsWithDelta(3600, $secondsUntilTtl, 2);

        Carbon::setTestNow();
    }

    public function test_cache_key_to_string_returns_key(): void
    {
        $key = new CacheKey('test:key', 3600);

        $this->assertEquals('test:key', (string) $key);
        $this->assertEquals('test:key', $key->__toString());
    }

    public function test_remember_stores_and_retrieves_value(): void
    {
        $key = new CacheKey('test:remember', 3600);

        $result = $key->remember(fn () => 'cached-value');

        $this->assertEquals('cached-value', $result);
        $this->assertEquals('cached-value', Cache::get('test:remember'));
    }

    public function test_remember_returns_cached_value_without_calling_callback(): void
    {
        Cache::put('test:remember', 'existing-value', 3600);

        $key = new CacheKey('test:remember', 3600);

        $callbackCalled = false;
        $result = $key->remember(function () use (&$callbackCalled) {
            $callbackCalled = true;
            return 'new-value';
        });

        $this->assertEquals('existing-value', $result);
        $this->assertFalse($callbackCalled);
    }

    public function test_forget_removes_cache_entry(): void
    {
        Cache::put('test:forget', 'value', 3600);

        $key = new CacheKey('test:forget', 3600);
        $result = $key->forget();

        $this->assertTrue($result);
        $this->assertNull(Cache::get('test:forget'));
    }

    public function test_put_stores_value_in_cache(): void
    {
        $key = new CacheKey('test:put', 3600);
        $key->put('stored-value');

        $this->assertEquals('stored-value', Cache::get('test:put'));
    }

    public function test_cached_returns_true_when_key_exists(): void
    {
        Cache::put('test:exists', 'value', 3600);

        $key = new CacheKey('test:exists', 3600);

        $this->assertTrue($key->cached());
    }

    public function test_cached_returns_false_when_key_does_not_exist(): void
    {
        $key = new CacheKey('test:not-exists', 3600);

        $this->assertFalse($key->cached());
    }

    public function test_get_returns_cached_value(): void
    {
        Cache::put('test:get', 'cached-value', 3600);

        $key = new CacheKey('test:get', 3600);

        $this->assertEquals('cached-value', $key->get());
    }

    public function test_get_returns_default_when_key_does_not_exist(): void
    {
        $key = new CacheKey('test:not-exists', 3600);

        $this->assertNull($key->get());
        $this->assertEquals('default', $key->get('default'));
    }

    public function test_cache_key_with_carbon_after_hours_ttl(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(22, 0, 0));

        $workingTtl = Carbon::now()->addHour();
        $afterHoursTtl = Carbon::now()->addHours(2);

        $key = new CacheKey('test:key', $workingTtl, $afterHoursTtl);

        $this->assertEquals($afterHoursTtl, $key->ttl);

        Carbon::setTestNow();
    }

    public function test_cache_key_with_mixed_ttl_types(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(10, 0, 0));

        $afterHoursTtl = Carbon::now()->addHours(2);

        $key = new CacheKey('test:key', 3600, $afterHoursTtl);

        $this->assertEqualsWithDelta(3600, Carbon::now()->diffInSeconds($key->ttl, false), 2);

        Carbon::setTestNow();
    }

    public function test_put_respects_ttl(): void
    {
        $key = new CacheKey('test:ttl', 1);
        $key->put('value');

        $this->assertTrue(Cache::has('test:ttl'));

        sleep(2);

        $this->assertFalse(Cache::has('test:ttl'));
    }
}
