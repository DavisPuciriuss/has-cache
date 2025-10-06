<?php

namespace Bunkuris\Support;

use Bunkuris\Contracts\CacheKeyManagerContract;

/**
 * @phpstan-import-type TemplateArray from CacheKeyManagerContract
 * @phpstan-import-type ParsedTemplateArray from CacheKeyManagerContract
 */
abstract class AbstractCacheKeyManager implements CacheKeyManagerContract
{
    public static function buildCacheKey(string $template, array $params = []): CacheKey
    {
        $templateData = static::getTemplate($template);
        $key = $templateData['pattern'];

        foreach ($params as $paramKey => $paramValue) {
            $key = str_replace('{' . $paramKey . '}', (string)$paramValue, $key);
        }

        if (preg_match('/\{[^}]+\}/', $key)) {
            throw new \InvalidArgumentException(
                "Missing parameters for cache key template '{$template}'. Key: {$key}"
            );
        }

        return new CacheKey(
            $key,
            $templateData['in_working_hours_ttl'],
            $templateData['after_working_hours_ttl'],
        );
    }

    /**
     * @return ParsedTemplateArray
     *
     * @throws \InvalidArgumentException
     */
    public static function getTemplate(string $template): array
    {
        /** @var TemplateArray $templates */
        $templates = static::getTemplates();

        if (!isset($templates[$template])) {
            throw new \InvalidArgumentException("Template '{$template}' not found.");
        }

        $result = $templates[$template];

        if (!isset($result['after_working_hours_ttl'])) {
            $result['after_working_hours_ttl'] = null;
        }

        /** @var ParsedTemplateArray $result */
        return $result;
    }
}
