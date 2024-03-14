<?php

use Illuminate\Support\Arr;
use App\Models\SiteConfig;
use TiagoF2\LaravelSiteConfig\Helpers\SiteConfigManager;
use TiagoF2\LaravelSiteConfig\Helpers\SerializeHelpers;


if (!function_exists('siteConfig')) {
    /**
     * function siteConfig
     *
     * @param ?string $key
     * @param mixed $default
     *
     * @return mixed
     */
    function siteConfig(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return app(SiteConfigManager::class);
        }

        return SiteConfigManager::get($key, $default);
    }
}

if (!function_exists('isUnserializable')) {
    /**
     * function isUnserializable
     *
     * @param mixed $data
     *
     * @return bool
     */
    function isUnserializable(mixed $data): bool
    {
        return SerializeHelpers::isUnserializable($data);
    }
}

if (!function_exists('tryUnserialize')) {
    /**
     * function tryUnserialize
     *
     * @param mixed $data
     * @param mixed $defaultOnFail
     * @param null|\Closure $catcher
     *
     * @return mixed
     */
    function tryUnserialize(
        mixed $data,
        mixed $defaultOnFail = null,
        ?Closure $catcher = null
    ): mixed {
        return SerializeHelpers::tryUnserialize(
            $data,
            $defaultOnFail,
            $catcher,
        );
    }
}
