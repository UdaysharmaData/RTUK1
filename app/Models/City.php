<?php

namespace App\Models;

use App\Contracts\CanHaveManyFaqs;
use App\Contracts\CanHaveManyInteractions;
use App\Contracts\CanHaveManySearchHistories;
use App\Contracts\CanHaveManyViews;
use App\Contracts\Redirectable;
use App\Modules\Event\Models\Event;
use App\Traits\BelongsToSite;
use App\Traits\HasAnalyticsTotalCountData;
use App\Traits\HasManyEvents;
use App\Traits\HasManyInteractions;
use App\Traits\HasManyPromotionalFeaturedEvents;
use App\Traits\HasManyPromotionalPages;
use App\Traits\RedirectableTrait;
use App\Traits\SlugTrait;
use App\Traits\HasManyViews;
use App\Traits\HasManyFaqs;
use App\Traits\Metable\HasOneMeta;
use App\Traits\AddUuidRefAttribute;
use App\Traits\FilterableListQueryScope;
use App\Traits\UseDynamicallyAppendedAttributes;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uploadable\HasManyUploads;
use App\Contracts\ConfigurableEventProperty;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Contracts\Metables\CanHaveMetableResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Models\Traits\BelongsToRegion;
use App\Models\Traits\HasManyVenues;
use App\Traits\ConfigurableEventPropertyNameSlugAttribute;
use App\Traits\Drafts\DraftTrait;
use App\Traits\HasManySearchHistories;
use App\Traits\SiteTrait;
use App\Traits\UseDynamicallySearchableAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class City extends Model implements
    CanHaveManyUploadableResource,
    CanHaveMetableResource,
    ConfigurableEventProperty,
    CanHaveManyViews,
    CanHaveManyInteractions,
    CanHaveManyFaqs,
    CanHaveManySearchHistories,
    Redirectable
{
    use HasFactory,
        SoftDeletes,
        SlugTrait,
        DraftTrait,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SiteTrait,
        UseDynamicallySearchableAttributes,
        HasManyUploads,
        HasOneMeta,
        HasManyFaqs,
        BelongsToSite,
        BelongsToRegion,
        HasManyVenues,
        HasManyEvents,
        ConfigurableEventPropertyNameSlugAttribute,
        HasManyPromotionalPages,
        HasManyPromotionalFeaturedEvents,
        HasManyViews,
        HasManySearchHistories,
        HasManyInteractions,
        HasAnalyticsTotalCountData,
        UseDynamicallyAppendedAttributes,
        FilterableListQueryScope,
        RedirectableTrait;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'site_id',
        'region_id',
        'description'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the city permanently will unlink it from events, combinations and others. This action is irreversible.'
    ];

     /**
     * The url on the website.
     *
     * @return Attribute
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return  static::getSite()?->url . '/cities/' . $this->slug;
            },
        );
    }
    public function events_relationship()
    {
        return $this->belongsToMany(Event::class, 'event_city_linking', 'city_id', 'event_id');
    }
}
