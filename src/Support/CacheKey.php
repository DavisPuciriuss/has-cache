<?php

namespace Bunkuris\Support;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

readonly class CacheKey
{
    public string $key;

    public Carbon $ttl;

    public function __construct(string $key, Carbon|int $in_working_hours_ttl, Carbon|int|null $after_working_hours_ttl = null)
    {
        $this->key = $key;

        if ($after_working_hours_ttl === null) {
            $this->ttl = $this->convertToCarbon($in_working_hours_ttl);

            return;
        }

        /** @var int $active_start_hour */
        $active_start_hour = config('has-cache.active_hour.start', 8);

        /** @var int $active_end_hour */
        $active_end_hour = config('has-cache.active_hour.end', 20);

        if (Carbon::now()->isBetween(Carbon::createFromTime(hour: $active_start_hour), Carbon::createFromTime(hour: $active_end_hour))) {
            $this->ttl = $this->convertToCarbon($in_working_hours_ttl);
        } else {
            $this->ttl = $this->convertToCarbon($after_working_hours_ttl);
        }
    }

    public function __toString(): string
    {
        return $this->key;
    }

    private function convertToCarbon(Carbon|int $value): Carbon
    {
        return is_int($value) ? Carbon::now()->addSeconds($value) : $value;
    }

    public function remember(Closure $callback): mixed
    {
        return Cache::remember($this->key, $this->ttl, $callback);
    }

    public function forget(): bool
    {
        return Cache::forget($this->key);
    }

    public function put(mixed $value): void
    {
        Cache::put($this->key, $value, $this->ttl);
    }

    public function cached(): bool
    {
        return Cache::has($this->key);
    }

    public function get(mixed $default = null): mixed
    {
        return Cache::get($this->key, $default);
    }
}
