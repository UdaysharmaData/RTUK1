<?php

namespace App\Models;

use App\Contracts\ConfigurableEventProperty;
use App\Contracts\Redirectable;
use App\Modules\Event\Models\Event;
use App\Traits\BelongsToSite;
use App\Traits\ConfigurableEventPropertyNameSlugAttribute;
use App\Traits\HasManyEvents;
use App\Traits\HasManyPromotionalFeaturedEvents;
use App\Traits\HasManyPromotionalPages;
use App\Traits\RedirectableTrait;
use DB;
use App\Traits\SiteTrait;
use App\Traits\SlugTrait;
use App\Traits\HasManyFaqs;
use App\Traits\HasManyViews;
use App\Contracts\CanHaveManyFaqs;

use App\Traits\Metable\HasOneMeta;
use App\Traits\AddUuidRefAttribute;
use App\Traits\HasManyInteractions;
use App\Contracts\CanHaveManyViews;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FilterableListQueryScope;
use App\Traits\Uploadable\HasManyUploads;
use App\Contracts\CanHaveManyInteractions;
use App\Contracts\CanHaveManySearchHistories;
use App\Traits\HasManySearchHistories;
use App\Traits\HasAnalyticsTotalCountData;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\UseDynamicallyAppendedAttributes;
use App\Contracts\Metables\CanHaveMetableResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\UseDynamicallySearchableAttributes;
use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Models\Traits\HasManyCities;
use App\Traits\Drafts\DraftTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Region extends Model implements
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
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SiteTrait,
        DraftTrait,
        SlugTrait,
        HasManyUploads,
        HasOneMeta,
        HasManyCities,
        HasManyFaqs,
        BelongsToSite,
        HasManyEvents,
        ConfigurableEventPropertyNameSlugAttribute,
        HasManyPromotionalPages,
        HasManyPromotionalFeaturedEvents,
        HasManyViews,
        HasManyInteractions,
        HasManyFaqs,
        HasManySearchHistories,
        HasAnalyticsTotalCountData,
        UseDynamicallySearchableAttributes,
        UseDynamicallyAppendedAttributes,
        FilterableListQueryScope,
        RedirectableTrait;

    protected $fillable = [
        'name',
        'site_id',
        'country',
        'promote_flag',
        'priority_number',
        'description'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the region permanently will unlink it from events, promotional_pages, combinations and others. This action is irreversible.'
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
                return  static::getSite()?->url . '/regions/' . $this->slug;
            }
        );
    }

    public function events_relationship()
    {
        return $this->belongsToMany(Event::class, 'event_region_linking', 'region_id', 'event_id');
    }
}
