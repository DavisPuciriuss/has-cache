---
name: has-cache
description: Build and work with HasCache features for automatic model cache invalidation, type-safe cache key managers, and working-hours-aware TTLs.
---

# HasCache Development

## When to use this skill

Use this skill when:
- Adding automatic cache invalidation to Eloquent models via the `HasCache` trait
- Creating or working with cache key managers (`cache:make:manager`, `cache:list:managers`)
- Defining cache key templates with working-hours-aware TTLs
- Testing cache behavior with `InteractsWithAsyncCache`
- Configuring `has-cache` settings (managers path, active hours)

## Installation & Configuration

Publish config:

```bash
php artisan vendor:publish --tag=has-cache-config
```

Config file (`config/has-cache.php`):

```php
return [
    'active_hour' => [
        'start' => 8,   // 8 AM
        'end'   => 20,  // 8 PM
    ],
    'managers_path' => 'app/Support/Cache', // where managers live; namespace derived automatically
];
```
