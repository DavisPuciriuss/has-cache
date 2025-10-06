<?php

namespace Bunkuris\Contracts;

use Bunkuris\Support\CacheKey;
use Illuminate\Support\Carbon;

/**
 * @phpstan-type TemplateArray array<string, array{pattern: string, in_working_hours_ttl: int|Carbon, after_working_hours_ttl?: int|Carbon}>
 * @phpstan-type ParsedTemplateArray array{pattern: string, in_working_hours_ttl: int|Carbon, after_working_hours_ttl: int|Carbon|null}
 */
interface CacheKeyManagerContract
{
    public static function buildCacheKey(string $template, array $params): CacheKey;

    public static function getTemplates(): array;

    public static function getTemplate(string $template): array;
}
