<?php

namespace TiagoF2\LaravelSiteConfig\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;
interface SiteConfigContract
{
    public function getValue(bool $parseValue = true): mixed;
    public function getRawValueAttribute(): mixed;
    public function getParsedValueAttribute(): mixed;
    public function setValueAttribute(mixed $value): void;
    public function updateValue(mixed $value, ?string $type = null): static;
    public function updateAs(?string $type, mixed $value): static;
    public function scopeActiveOnly(Builder $query, bool $activeOnly = true): Builder;
    public function getKeyNotationAttribute(): string;
    public function getFetchedInAttribute(): string;
}
