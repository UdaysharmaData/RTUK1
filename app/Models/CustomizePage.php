<?php

namespace App\Models;

use App\Contracts\CanHaveManyFaqs;
use App\Contracts\CanHaveManyInteractions;
use App\Contracts\CanHaveManySearchHistories;
use App\Contracts\CanHaveManyViews;
use App\Contracts\FilterableListQuery;
use App\Contracts\Redirectable;
use App\Enums\PageStatus;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Services\SoftDeleteable\Contracts\SoftDeleteableContract;
use App\Services\SoftDeleteable\Traits\ActionMessages;
use App\Traits\AddMetaTrait;
use App\Traits\AddUuidRefAttribute;
use App\Traits\BelongsToSite;
use App\Traits\Drafts\DraftTrait;
use App\Traits\FilterableListQueryScope;
use App\Traits\HasAnalyticsTotalCountData;
use App\Traits\HasManyFaqs;
use App\Traits\RedirectableTrait;
use App\Traits\SiteTrait;
use App\Traits\HasManyInteractions;
use App\Traits\HasManySearchHistories;
use App\Traits\HasManyViews;
use App\Traits\Metable\HasOneMeta;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\UseDynamicallyAppendedAttributes;
use App\Traits\UseSiteGlobalScope;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class CustomizePage extends Model implements
    CanUseCustomRouteKeyName,
    CanHaveManyFaqs,
    CanHaveManyViews,
    CanHaveManyInteractions,
    FilterableListQuery,
    SoftDeleteableContract,
    CanHaveManySearchHistories,
    Redirectable
{
    use Searchable {
        search as parentSearch;
    }

    use HasFactory,
        SoftDeletes,
        SiteTrait,
        UuidRouteKeyNameTrait,
        DraftTrait,
        AddUuidRefAttribute,
        SiteIdAttributeGenerator,
        UseSiteGlobalScope,
        HasManyFaqs,
        HasOneMeta,
        HasManyViews,
        HasManySearchHistories,
        HasManyInteractions,
        HasAnalyticsTotalCountData,
        UseDynamicallyAppendedAttributes,
        BelongsToSite,
        FilterableListQueryScope,
        AddMetaTrait,
        ActionMessages,
        RedirectableTrait;

    /**
     * @var string[]
     */
    protected $table = 'customize_pages';

    protected $fillable = [
        'name',
        'slug',
        'chunks',
        'html_content',
        'status'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'status' => PageStatus::class
    ];

    /**
     * @var string[]
     */
    protected $with = [
        'faqs',
        'meta',
    ];

    /**
     * @var string[]
     */
    public static $actionMessages = [
        'force_delete' => 'Deleting a page permanently will unlink it from events and other associated services within the platform.'
    ];

    /**
     * Scope a query to only include offline pages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOffline($query)
    {
        $query->where('status', 0);
    }

    /**
     * Scope a query to only include online pages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnline($query)
    {
        $query->where('status', 1);
    }

    /**
     * Get the indexable data array for the model.
     * This is used by Laravel Scout to build the search index.
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => '',
            'chunks' => '',
            'html_content' => '',
            'slug' => '',
        ];
    }

    /**
     * Override the default Laravel scout search method.
     *
     * @param  string $query
     * @param  \Closure  $callback
     * @return \Laravel\Scout\Builder
     */
    public static function search($query = '', $callback = null): \Laravel\Scout\Builder
    {
        return static::parentSearch($query, $callback)->query(function ($builder) {
            $builder->withOnly([])->withoutAppends()
                ->online()
                ->where('site_id', static::getSite()->id)
                ->orderBy('name');
        });
    }
}
