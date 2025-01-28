<?php

namespace App\Models;

use App\Traits\SiteTrait;
use App\Enums\SocialPlatformEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Social extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, SiteTrait;

    protected $table = 'socials';

    protected $fillable = [
        'socialable_type',
        'socialable_id',
        'platform',
        'url',
        'is_social_auth'
    ];

    protected $casts = [
        'platform' => SocialPlatformEnum::class,
        'is_social_auth' => 'boolean'
    ];

    protected $appends = [
        'handle'
    ];

    /**
     * @return MorphTo
     */
    public function socialable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the social handler.
     * TODO: Extract the handle from the url.
     *
     * @return Attribute
     */
    protected function handle(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // TODO: Improve on the regex below and ensure to return the handle for every platform

                if ($this->platform == SocialPlatformEnum::Twitter) {
                    preg_match("|https?://(www\.)?twitter\.com/(#!/)?@?([^/]*)|", $this->url, $matches);
                    return $matches[3] ?? null;
                }

                return $value;
            }
        );
    }

    // /**
    //  * Get the platform.
    //  * ERROR: Cannot instantiate enum App\\Enums\\SocialPlatformEnum
    //  *
    //  * @return Attribute
    //  */
    // protected function platform(): Attribute
    // {
    //     return Attribute::make(
    //         get: function ($value) {
    //             $domain = static::getSite()?->domain;

    //             if ($value == SocialPlatformEnum::Facebook && $this->handle == "run4cancer") {
    //                 if ($domain) {
    //                     switch ($domain) {
    //                         case SiteEnum::SportForCharity:
    //                             return "Sail-4-Cancer-118921558157261";
    //                             break;
    //                         case SiteEnum::RunForCharity:
    //                             return "run4cancer";
    //                             break;
    //                         case SiteEnum::CycleForCharity:
    //                             return "Bike4CancerUK";
    //                             break;
    //                     }
    //                 }
    //             }

    //             if ($value == SocialPlatformEnum::Twitter && $this->handle == "run4cancer") {
    //                 if ($domain) {
    //                     switch ($domain) {
    //                         case SiteEnum::SportForCharity:
    //                             return "sail4cancer";
    //                             break;
    //                         case SiteEnum::RunForCharity:
    //                             return "run4cancer";
    //                             break;
    //                         case SiteEnum::CycleForCharity:
    //                             return "Bike4CancerUK";
    //                             break;
    //                     }
    //                 }
    //             }

    //             return $value;
    //         },
    //     );
    // }

}
