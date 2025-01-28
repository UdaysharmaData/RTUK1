<?php

namespace App\Models;

use App\Contracts\CanHaveManyFaqs;
use App\Contracts\CanHaveManyInteractions;
use App\Contracts\CanHaveManySearchHistories;
use App\Contracts\CanHaveManyViews;
use App\Contracts\FilterableListQuery;
use App\Contracts\Redirectable;
use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Traits\AddMetaTrait;
use App\Traits\AddUuidRefAttribute;
use App\Traits\BelongsToSite;
use App\Traits\Drafts\DraftTrait;
use App\Traits\FilterableListQueryScope;
use App\Traits\HasAnalyticsTotalCountData;
use App\Traits\HasManyFaqs;
use App\Traits\HasManyInteractions;
use App\Traits\HasManySearchHistories;
use App\Traits\HasManyViews;
use App\Traits\Metable\HasOneMeta;
use App\Traits\RedirectableTrait;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\SiteTrait;
use App\Traits\SlugTrait;
use App\Traits\Uploadable\HasManyUploads;
use App\Traits\UseDynamicallyAppendedAttributes;
use App\Traits\UseDynamicallySearchableAttributes;
use App\Traits\UseSiteGlobalScope;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Combination extends Model implements
    CanUseCustomRouteKeyName,
    CanHaveManyFaqs,
    CanHaveManyUploadableResource,
    CanHaveManyViews,
    CanHaveManyInteractions,
    FilterableListQuery,
    CanHaveManySearchHistories,
    Redirectable
{
    use HasFactory,
        SoftDeletes,
        SiteTrait,
        DraftTrait,
        SiteIdAttributeGenerator,
        BelongsToSite,
        UseSiteGlobalScope,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SlugTrait,
        HasManyFaqs,
        HasOneMeta,
        HasManyUploads,
        AddMetaTrait,
        HasManyViews,
        HasManyInteractions,
        HasAnalyticsTotalCountData,
        HasManySearchHistories,
        UseDynamicallySearchableAttributes,
        UseDynamicallyAppendedAttributes,
        FilterableListQueryScope,
        RedirectableTrait;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'event_category_id',
        'region_id',
        'city_id',
        'venue_id',
        'series_id',
        'date',
        'year',
        'month',
        'promote_flag',
        'priority_number',
        'path'
    ];

    /**
     * @var string[]
     */
    protected $with = [
        // 'eventCategory',
        // 'region',
        // 'city',
        // 'venue',
        'faqs',
        'meta',
        'image',
        'gallery',
        'events'
    ];

    /**
     * @return BelongsTo
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * @return HasManyThrough
     */
    public function events(): HasManyThrough
    {
        return $this->hasManyThrough(
            Event::class,
            EventEventCategory::class,
            'event_category_id',
            'id',
            'event_category_id',
            'event_id'
        );
    }

    /**
     * @return BelongsTo
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return BelongsTo
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * The url on the website.
     *
     * @return Attribute
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return  static::getSite()?->url . $this->path;
            },
        );
    }
}
