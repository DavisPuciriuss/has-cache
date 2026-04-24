<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active Hours Configuration
    |--------------------------------------------------------------------------
    |
    | Define the working/active hours for your application.
    | Cache TTLs can be different during and outside these hours.
    |
    */
    'active_hour' => [
        'start' => 8,   // 8 AM (8:00)
        'end' => 20,    // 8 PM (20:00)
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Managers Path
    |--------------------------------------------------------------------------
    |
    | The path (relative to the application base path) where cache key managers
    | are stored. The namespace is derived automatically from this path.
    |
    */
    'managers_path' => 'app/Support/Cache',
];