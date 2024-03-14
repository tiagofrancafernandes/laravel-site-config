<?php

namespace TiagoF2\LaravelSiteConfig\Helpers;

use TiagoF2\LaravelSiteConfig\Models\SiteConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Throwable;
use Exception;
use TiagoF2\LaravelSiteConfig\Contracts\SiteConfigContract;

class SiteConfigManager
{
    public const TYPES = [ // TODO ?: change to use Enum??
        'long-text' => [
            'html',
            'long-text',
            'text',
        ],
        'string' => [
            'storage_item',

            'string',

            'asset',
            'url',
            'route',
        ],
        'bool' => [
            'bool',
        ],
        'null' => [
            'null',
        ],
        'numeric' => [
            'double',
            'float',
            'integer',
            'numeric',
        ],
        'collection' => [
            'key-pair',
            'collection',
        ],
        'array' => [
            'array',
        ],
    ];

    public static function parseKeyNotation(
        ?string $keyNotation = null,
        ?bool $throwOnError = null,
    ): ?array {
        if (!$keyNotation) {
            static::handleError('Invalid "keyNotation". Must be valid screen', $throwOnError);

            return null;
        }

        $keys = array_filter(explode('.', $keyNotation, 2));

        $group = trim(strval($keys[0] ?? null));
        $key = trim(strval($keys[1] ?? null));

        if (!$group || !$key) {
            static::handleError('Invalid "keyNotation". Must be like "group.key"', $throwOnError);

            return null;
        }

        return [
            'group' => $group,
            'key' => $key,
        ];
    }

    public static function handleError(
        null|int|float|string|Throwable $data = null,
        ?bool $throwOnError = null,
    ): void {
        try {
            if (!$data) {
                return;
            }

            $throwOnError ??= config('app.debug', false);

            app('log')->error($data);

            if (!$throwOnError) {
                return;
            }

            $data = is_object($data) && is_a($data, Throwable::class) ? $data : new Exception(strval($data), 50);

            throw $data;
        } catch (Throwable $th) {
            app('log')->error($th);

            if (!$throwOnError) {
                return;
            }

            throw $th;
        }
    }

    public static function query(): Builder
    {
        return static::getSiteConfigModel()::query();
    }

    public static function removeFromCache(string $keyNotation): bool
    {
        $cacheKey = static::getCacheKey($keyNotation);
        cache()->forget($cacheKey);

        return true;
    }

    public static function config(
        string $keyNotation,
        bool $revalidateCache = false,
    ): ?SiteConfig {
        $keys = static::parseKeyNotation($keyNotation);

        $group = $keys['group'] ?? null;
        $key = $keys['key'] ?? null;

        if (!$group || !$key) {
            return null;
        }

        $cacheKey = static::getCacheKey($keyNotation);

        if ($revalidateCache) {
            static::removeFromCache($keyNotation);
        }

        return cache()->remember(
            $cacheKey,
            static::getCacheTime(),
            fn () => SiteConfig::activeOnly()
                ->whereNotNull('group')
                ->whereNotNull('key')
                ->where('group', $group)
                ->where('key', $key)
                ->first()
        );
    }

    public static function getCacheKey(string $keyNotation): string
    {
        $prefix = trim(strval(config('site-config.cache.prefix') ?: ''));
        $keys = implode('', array_values(static::parseKeyNotation($keyNotation)));

        return str_replace(
            [' ', '.'],
            '-',
            implode('-', [
                $prefix,
                static::getSiteConfigModel(),
                $keys,
            ])
        );
    }

    public static function getCacheTime(): int
    {
        if (!filter_var(config('site-config.cache.enabled', true), FILTER_VALIDATE_BOOL)) {
            return 0;
        }

        return filter_var(config('site-config.cache.cache_time', 3600), FILTER_VALIDATE_INT) ?: 3600;
    }

    /**
     * set function
     *
     * Alias to `put`
     *
     * @param string $keyNotation
     * @param mixed $value
     * @param string|null $type
     * @param boolean $active
     * @return null|boolean|SiteConfigContract
     */
    public static function set(
        string $keyNotation,
        mixed $value,
        ?string $type = null,
        bool $active = true,
    ): null|bool|SiteConfigContract {
        return static::put(
            $keyNotation,
            $value,
            $type,
            $active,
        );
    }

    public static function put(
        string $keyNotation,
        mixed $value,
        ?string $type = null,
        bool $active = true,
    ): null|bool|SiteConfigContract {
        $keys = static::parseKeyNotation($keyNotation);

        $group = $keys['group'] ?? null;
        $key = $keys['key'] ?? null;

        if (!$group || !$key) {
            return false;
        }

        $type ??= static::getDefaultTypeByValue($value);

        $value = match ($type) {
            'storage_item' => is_string($value) ? $value : null,
            'html' => is_string($value) ? htmlentities($value) : null,
            'text', 'long-text' => is_string($value) ? $value : null,
            'asset' => is_string($value) ? $value : null,
            'url' => filter_var($value, FILTER_VALIDATE_URL) ?: null,
            'route' => $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'collection', 'key-pair', 'array' => is_array($value) ? $value : [],
            default => $value,
        };

        /**
         * @var SiteConfig $configRecord
         */
        $configRecord = static::config($keyNotation, true);

        $dataToSave = [
            'group' => $group,
            'key' => $key,
            'value' => $value,
            'type' => $type,
            'active' => $active,
        ];

        if ($configRecord) {
            $configRecord->update($dataToSave);

            static::removeFromCache($keyNotation);

            return $configRecord;
        }

        $configRecord = SiteConfig::create($dataToSave);

        return $configRecord ?: false;
    }

    public static function get(
        string $keyNotation,
        mixed $default = null,
        bool $revalidateCache = false,
    ): mixed {
        /**
         * @var SiteConfig $configRecord
         */
        $configRecord = static::config($keyNotation, $revalidateCache);

        if (!$configRecord) {
            return static::getDefaults($keyNotation, $default, $revalidateCache);
        }

        return static::parseConfigValue(
            $configRecord?->value,
            $configRecord?->type,
            $default,
        );
    }

    public static function parseConfigValue(
        mixed $value,
        mixed $type,
        mixed $default = null,
    ): mixed {
        $types = array_reduce(static::TYPES, fn ($carry, $item) => array_merge($carry, $item), []);

        $errorVal = '_ERROR_' . uniqid();

        $type = is_string($type) ? strtolower($type) : null;

        if (!in_array($type, $types, true)) {
            return $default;
        }

        $value = is_string($value) ? SerializeHelpers::tryUnserialize($value) : $errorVal;

        if (is_string($value) && $value === $errorVal) {
            return $default;
        }

        $toReturn = match ($type) {
            'storage_item' => is_string($value) ? $value : null, // TODO: return image storage URL
            'html' => is_string($value) ? html_entity_decode($value) : null,
            'string', 'text', 'long-text' => is_string($value) ? $value : null,
            'asset' => is_string($value) ? asset($value) : asset(''),
            'url' => filter_var($value, FILTER_VALIDATE_URL),
            'route' => is_string($value) && \Route::has($value) ? route($value) : null,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'key-pair' => is_array($value) ? collect($value) : collect(),
            'collection' => collect($value),
            'array' => is_array($value) ? $value : [],
            default => $value,
        };

        return $toReturn;
    }

    public static function getDefaultTypeByValue(mixed $value, ?string $default = null): string
    {
        if (is_object($value) && is_a($value, \Illuminate\Support\Collection::class)) {
            return 'collection';
        }

        $default ??= is_numeric($value) ? 'numeric' : null;
        $default ??= is_array($value) ? 'array' : null;
        $default ??= is_null($value) ? 'null' : null;
        $default ??= is_string($value) ? 'string' : null;
        $default ??= is_bool($value) ? 'bool' : null;
        $default ??= is_object($value) ? 'null' : null;

        if (is_null($default) && is_string($value) && strlen($value) > 200) {
            return 'long-text';
        }

        $namedType = match (strtolower(gettype($value))) {
            'double', 'float', 'integer', 'numeric' => 'numeric',
            'string' => 'string',
            'boolean', 'bool' => 'bool',
            'array' => 'array',
            'null' => 'null',
            default => $default ?? gettype($value),
        };

        $types = array_reduce(static::TYPES, fn ($carry, $item) => array_merge($carry, $item), []);

        if (!in_array($namedType, $types)) {
            return 'text';
        }

        return array_key_exists($namedType, static::TYPES) ? $namedType : 'text';
    }

    /**
     * forget function
     *
     * Alias to `delete`
     *
     * @param string $keyNotation
     * @param boolean $revalidateCache
     * @return boolean
     */
    public static function forget(
        string $keyNotation,
        bool $revalidateCache = true,
    ): bool {
        return static::delete($keyNotation, $revalidateCache);
    }

    public static function delete(
        string $keyNotation,
        bool $revalidateCache = true,
    ): bool {
        $keys = static::parseKeyNotation($keyNotation);

        $group = $keys['group'] ?? null;
        $key = $keys['key'] ?? null;

        if (!$group || !$key) {
            return false;
        }

        /**
         * @var SiteConfig $configRecord
         */
        $configRecord = static::config($keyNotation, $revalidateCache);

        if (!$configRecord || $configRecord?->persistent) {
            return false;
        }

        if ($configRecord?->delete()) {
            $cacheKey = static::getCacheKey($keyNotation);
            cache()->forget($cacheKey);

            return true;
        }

        return false;
    }

    public static function getDefaults(
        string $keyNotation,
        mixed $default = null,
        bool $revalidateCache = false,
    ): mixed {
        $keys = static::parseKeyNotation($keyNotation, throwOnError: false);

        $group = $keys['group'] ?? null;
        $key = $keys['key'] ?? null;

        if (!$group || !$key) {
            return $default;
        }

        $notFoundValue = '_NOT_FOUND_' . uniqid();

        $value = Arr::get(
            (array) config('site-config.hard_defaults', []),
            $keyNotation,
            $notFoundValue
        );

        if (!is_string($value) || $value !== $notFoundValue) {
            return $value;
        }

        if (!config('site-config.laravel_config.use_laravel_config', true)) {
            return $default;
        }

        return config($keyNotation, $default);
    }

    /**
     * getModel function
     *
     * @param bool $toInstantiate
     *
     * @return string|object
     */
    public static function getModel(bool $toInstantiate = false): string|object
    {
        return $toInstantiate ? app(static::getSiteConfigModel()) : static::getSiteConfigModel();
    }

    public static function getSiteConfigModel(): string
    {
        return config('site-config.models.site_config', SiteConfig::class);
    }
}
