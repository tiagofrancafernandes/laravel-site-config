<?php

namespace TiagoF2\LaravelSiteConfig\Helpers;

use TiagoF2\LaravelSiteConfig\Helpers\SiteConfigManager;

class SerializeHelpers
{
    /**
     * function isUnserializable
     *
     * @param mixed $data
     *
     * @return bool
     */
    public static function isUnserializable(mixed $data): bool
    {
        $data = is_string($data) ? trim($data) : null;

        if (!$data || (strlen($data) === 2 && $data !== 'N;') || strlen($data) < 4) {
            return false;
        }

        foreach (['N;', ';', ':'] as $partial) {
            if (
                (
                    str_contains($data[1] ?? '', $partial)
                    || str_contains($data, $partial)
                ) && (
                    str_ends_with($data, ';')
                    || str_ends_with($data, '}')
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * function tryUnserialize
     *
     * @param mixed $data
     * @param mixed $defaultOnFail
     * @param null|\Closure $catcher
     *
     * @return mixed
     */
    public static function tryUnserialize(
        mixed $data,
        mixed $defaultOnFail = null,
        ?\Closure $catcher = null
    ): mixed {
        try {
            if (!static::isUnserializable($data)) {
                return $defaultOnFail;
            }

            return unserialize($data);
        } catch (\Throwable $th) {
            if ($catcher) {
                $catcher($th);
            }

            return $defaultOnFail;
        }
    }
}

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
