<?php

use TiagoF2\LaravelSiteConfig\Models\SiteConfig;

return [
    'models' => [
        'site_config' => SiteConfig::class,
    ],
    'cache' => [
        'prefix' => '',
        'enabled' => filter_var(
            env('SITE_CONFIG_CACHE_ENABLED', true),
            FILTER_VALIDATE_BOOL
        ),
        'cache_time' => filter_var(env('SITE_CONFIG_CACHE_TIME', 3600), FILTER_VALIDATE_INT) ?: 3600,
    ],

    /**
     * Tables config
     */

    'tables' => [
        'config_table' => 'site_configs',
    ],

    /**
     * If use Laravel config() when config was not found on DB
     */
    'laravel_config' => [
        /**
         * When a config was not found, will use config(...) to get Laravel config
         */
        'use_laravel_config' => true,
    ],

    /**
     * Default values to config when not found on DB
     */
    'hard_defaults' => [
        'group-demo' => [
            'key' => 'default config value', // 'group-demo.key'
        ],
    ],
];
