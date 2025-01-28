<?php

namespace App\Models;

use App\Contracts\CanHaveManyFaqs;
use App\Contracts\CanHaveManyInteractions;
use App\Contracts\CanHaveManySearchHistories;
use App\Contracts\CanHaveManyViews;
use App\Modules\Event\Models\Event;
use App\Contracts\Redirectable;
use App\Traits\BelongsToSite;
use App\Traits\HasAnalyticsTotalCountData;
use App\Traits\HasManyEvents;
use App\Traits\RedirectableTrait;
use App\Traits\SlugTrait;
use App\Traits\FilterableListQueryScope;
use App\Traits\HasManyInteractions;
use App\Traits\HasManyPromotionalFeaturedEvents;
use App\Traits\HasManyPromotionalPages;
use App\Traits\HasManyFaqs;
use App\Traits\HasManyViews;
use App\Traits\Metable\HasOneMeta;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UseDynamicallyAppendedAttributes;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uploadable\HasManyUploads;
use App\Contracts\ConfigurableEventProperty;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Contracts\Metables\CanHaveMetableResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Models\Traits\BelongsToCity;
use App\Traits\ConfigurableEventPropertyNameSlugAttribute;
use App\Traits\Drafts\DraftTrait;
use App\Traits\HasManySearchHistories;
use App\Traits\SiteTrait;
use App\Traits\UseDynamicallySearchableAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Venue extends Model implements
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
        DraftTrait,
        SlugTrait,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SiteTrait,
        HasManyUploads,
        HasOneMeta,
        HasManyFaqs,
        BelongsToSite,
        BelongsToCity,
        HasManyEvents,
        ConfigurableEventPropertyNameSlugAttribute,
        HasManyPromotionalPages,
        HasManyPromotionalFeaturedEvents,
        HasManyViews,
        HasManySearchHistories,
        HasManyInteractions,
        HasAnalyticsTotalCountData,
        UseDynamicallySearchableAttributes,
        UseDynamicallyAppendedAttributes,
        FilterableListQueryScope,
        RedirectableTrait;


    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'site_id',
        'city_id',
        'description'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the venue permanently will unlink it from events, combinations and others. This action is irreversible.'
    ];


    /**
     * The url on the website.
     *
     * @return Attribute
     */
    public function url(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return  static::getSite()?->url . '/venues/' . $this->slug;
            },
        );
    }
    public function events_relationship()
    {
        return $this->belongsToMany(Event::class, 'event_venues_linking', 'venue_id', 'event_id');
    }
}
