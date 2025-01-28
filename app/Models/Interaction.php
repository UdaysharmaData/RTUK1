<?php

namespace App\Models;

use App\Contracts\CanHaveAnalyticsMetadata;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\User\Models\User;
use App\Services\Analytics\Enums\InteractionTypeEnum;
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

class Interaction extends Model implements CanUseCustomRouteKeyName, CanHaveAnalyticsMetadata
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
     * @var string[]
     */
    protected $fillable = [
        'type'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'type' => InteractionTypeEnum::class
    ];

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
    public function interactable(): MorphTo
    {
        return $this->morphTo();
    }
}
