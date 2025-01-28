<?php

namespace App\Models;

use App\Contracts\CanHaveAnalyticsMetadata;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\User\Models\User;
use App\Services\DataCaching\Traits\CacheQueryBuilder;
use App\Traits\AddUserIdAttribute;
use App\Traits\AddUuidRefAttribute;
use App\Traits\BelongsToSite;
use App\Traits\HasAnalyticsMetadata;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class View extends Model implements CanUseCustomRouteKeyName, CanHaveAnalyticsMetadata
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SiteIdAttributeGenerator,
        AddUserIdAttribute,
        HasAnalyticsMetadata,
        CacheQueryBuilder,
        BelongsToSite;

//    /**
//     * @var string[]
//     */
//    protected $with = ['metadata'];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    /**
     * @return MorphTo
     */
    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }
}
