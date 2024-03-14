<?php

namespace TiagoF2\LaravelSiteConfig\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use TiagoF2\LaravelSiteConfig\Contracts\SiteConfigContract;
use TiagoF2\LaravelSiteConfig\Concers\SiteConfigMethods;

#[\AllowDynamicProperties]
class SiteConfig extends Model implements SiteConfigContract
{
    use HasFactory;
    use SiteConfigMethods;

    protected $fillable = [
        'group',
        'key',
        'type',
        'value',
        'active',
        'persistent',
    ];

    protected $casts = [
        'active' => 'boolean',
        'persistent' => 'boolean',
    ];

    protected $appends = [
        'keyNotation',
        'fetchedIn',
    ];
}
