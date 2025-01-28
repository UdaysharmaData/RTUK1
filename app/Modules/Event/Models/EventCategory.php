<?php

namespace App\Modules\Event\Models;

use App\Contracts\Redirectable;
use App\Traits\RedirectableTrait;
use Str;
use Carbon\Carbon;
use Laravel\Scout\Searchable;
use App\Contracts\CanHaveManyViews;
use Illuminate\Database\Eloquent\Model;
use App\Contracts\CanHaveManyInteractions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\SlugTrait;
use App\Traits\SiteTrait;
use App\Traits\HasManyFaqs;
use App\Http\Helpers\AccountType;
use App\Contracts\CanHaveManyFaqs;
use App\Contracts\CanHaveManySearchHistories;
use App\Traits\Metable\HasOneMeta;
use App\Traits\HasAnalyticsTotalCountData;

use App\Traits\HasManyViews;
use App\Traits\AddUuidRefAttribute;
use App\Traits\HasManyInteractions;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\Medalable\HasManyMedals;
use App\Traits\Uploadable\HasManyUploads;
use App\Traits\FilterableListQueryScope;
use App\Enums\EventCategoryVisibilityEnum;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\UseDynamicallyAppendedAttributes;
use App\Contracts\Metables\CanHaveMetableResource;
use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Enums\EventStateEnum;
use App\Modules\Event\Models\Relations\EventCategoryRelations;
use App\Modules\Event\Models\Traits\EventCategoryQueryScopeTrait;
use App\Traits\Drafts\DraftTrait;
use App\Traits\HasManySearchHistories;

class EventCategory extends Model implements CanHaveManyUploadableResource, CanHaveMetableResource, CanHaveManyFaqs, CanHaveManyViews, CanHaveManyInteractions, CanHaveManySearchHistories, Redirectable
{
    use Searchable {
        search as parentSearch;
    }

    use HasFactory,
        SoftDeletes,
        DraftTrait,
        SiteTrait,
       // SlugTrait,
        DraftTrait,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        HasManyUploads,
        HasOneMeta,
        EventCategoryRelations,
        EventCategoryQueryScopeTrait,
        FilterableListQueryScope,
        HasAnalyticsTotalCountData,
        HasManyFaqs,
        HasManyMedals,
        HasManySearchHistories,
        HasManyViews,
        HasManyInteractions,
        UseDynamicallyAppendedAttributes,
        RedirectableTrait;

    protected $table = 'event_categories';

    protected $fillable = [
        'site_id',
        'visibility',
        'name',
        'slug',
        'description',
        'color',
        'distance_in_km',
        'promote_flag',
        'priority_number',
    ];

    protected $casts = [
        'distance_in_km' => 'double',
        'visibility' => EventCategoryVisibilityEnum::class
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'formatted_distance_in_km',
        'formatted_distance_in_miles',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the event category permanently will unlink it from events, event third parties, participants, event pages, combinations, enquiries and others. This action is irreversible.'
    ];

    /**
     * Update the name based on the site making the request
     *
     * @return Attribute
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                    return $value . html_entity_decode("&ensp; &#183; &ensp;") . $this->site->name;
                }

                return $value;
            },
        );
    }

    /**
     * Append KM to the distance_in_km value
     *
     * @return Attribute
     */
    protected function formattedDistanceInKm(): Attribute
    {
        return Attribute::make(
            get: function ($value) {

                return $this->distance_in_km ? $this->distance_in_km . ' KM' : 'NaN';
            },
        );
    }

    /**
     * Convert distance_in_km to miles
     *
     * @return Attribute
     */
    protected function formattedDistanceInMiles(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $multiplier = 0.62137119; // 1KM in miles

                return $this->distance_in_km ? round(($this->distance_in_km * $multiplier), 2) . ' Mi' : 'NaN';
            },
        );
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
                return static::getSite()?->url . "/categories/$this->slug";
            },
        );
    }

    // /**
    //  * TODO: Update this after creating the RaceFile model
    //  */
    // public function calculateRankingsAverage($year, $gender)
    // {
    //     $totalTime = 0;

    //     $events = $this->events()
    //         ->whereYear('start_date', '=', $year)
    //         ->whereHas('raceFile', function($query) {
    //             $query->where('state', 'published');
    //         })->whereHas('raceResults.rawRaceResult', function($query) use ($gender) {
    //            $query->where('gender', $gender);
    //         })->get();

    //     foreach ($events as $event) {
    //         $totalTime += Time::timeInSeconds($event->rankingsAverage($gender));
    //     }

    //     return $events->count() && $totalTime ? Time::format(round($totalTime / $events->count())) : null;
    // }

    /**
     * List all the event categories associated with the site.
     *
     * @return Collection
     */
    public static function listAll(): Collection
    {
        $categories = static::whereHas('site', function ($query) {
            $query->where('id', static::getSite()?->id);
        });

        if (AccountType::isParticipant()) {
            $categories = $categories->whereHas('events', function ($query) {
                // $query->state(EventStateEnum::Live);
                $query->estimated(Event::INACTIVE);
                $query->archived(Event::INACTIVE);
                $query->partnerEvent(Event::ACTIVE);
                $query->where('status', Event::ACTIVE);
                $query->where('end_date', '>', Carbon::now());
            });
        }

        $categories = $categories->get();

        return $categories;
    }

    /**
     * List all the event categories associated with their site and number of places.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function dropdown(): \Illuminate\Support\Collection
    {
        $categories = static::with(['site.setting.settingCustomFields' => function ($query) {
            $query->whereIn('key', ['classic_membership_default_places', 'premium_membership_default_places', 'two_year_membership_default_places', 'partner_membership_default_places']);
        }])->whereHas('site', function ($query) {
            $query->makingRequest();
        });

        $categories = $categories->get();

        if (AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
            foreach ($categories as $key => $category) {
                $categories[$key]['name'] = $category->name . " . " . $category->site->name;
            }
        }

        return $categories;
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
            'name' => ''
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
            $builder->withoutAppends()
                ->withOnly(['image'])
                ->withCount(['events' => function ($q) {
                    $q->state(EventStateEnum::Live);
                }])
                ->visibility(EventCategoryVisibilityEnum::Public)
                ->where('site_id', static::getSite()->id)
                ->orderBy('name')
                ->orderByDesc('events_count');
        });
    }
}
