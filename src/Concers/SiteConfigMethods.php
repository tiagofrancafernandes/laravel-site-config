<?php

namespace TiagoF2\LaravelSiteConfig\Concers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use TiagoF2\LaravelSiteConfig\Helpers\SiteConfigManager;

trait SiteConfigMethods
{
    public function getValue(bool $parseValue = true): mixed
    {
        return $parseValue ? $this->getParsedValueAttribute() : $this->getRawValueAttribute();
    }

    public function getRawValueAttribute(): mixed
    {
        return $this->value;
    }

    public function getParsedValueAttribute(): mixed
    {
        return SiteConfigManager::parseConfigValue(
            $this->value,
            $this?->type,
        );
    }

    public function setValueAttribute(mixed $value): void
    {
        $type = SiteConfigManager::getDefaultTypeByValue($value, $this->attributes['type'] ?? null);

        $this->attributes['value'] = serialize($value);
        $this->attributes['type'] = $type;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (str_starts_with($method, 'updateAs')) {
            $type = explode('updateAs', $method, 2)[1] ?? null;

            $type = strtolower("{$type}");

            return $this->{'updateAs'}($type, $parameters[0] ?? null);
        }

        return parent::__call($method, $parameters);
    }

    public function updateValue(mixed $value, ?string $type = null): static
    {
        return $this->updateAs($value, $type);
    }

    public function updateAs(?string $type, mixed $value): static
    {
        $type ??= SiteConfigManager::getDefaultTypeByValue($value, $this->{'type'} ?? null);

        $this->update([
            'value' => $value,
            'type' => $type,
        ]);

        return $this;
    }

    public function scopeActiveOnly(Builder $query, bool $activeOnly = true): Builder
    {
        return $query->where('active', $activeOnly);
    }

    public function getKeyNotationAttribute(): string
    {
        return implode(
            '.',
            [
                $this->group,
                $this->key
            ]
        );
    }

    public function getFetchedInAttribute(): string
    {
        return date('c');
    }

    public static function put(
        string $keyNotation,
        mixed $value,
        string $type,
        bool $active = true,
    ): null|bool|static {
        return SiteConfigManager::put(
            $keyNotation,
            $value,
            $type,
            $active,
        );
    }
}
