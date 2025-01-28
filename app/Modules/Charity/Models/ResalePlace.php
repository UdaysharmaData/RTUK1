<?php

namespace App\Modules\Charity\Models;

use Carbon\Carbon;
use App\Traits\SiteTrait;
use App\Modules\Event\Models\Event;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Enums\ResaleRequestStateEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Charity\Models\Relations\ResalePlaceRelations;

class ResalePlace extends Model
{
    use HasFactory, SiteTrait, ResalePlaceRelations, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'resale_places';

    protected $fillable = [
        'charity_id',
        'event_id',
        'places',
        'taken',
        'unit_price',
        // 'discount'
    ];

    protected $appends = [
        'available'
    ];

    /**
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if ($model->discount) {
                $model->discount = static::determineDiscount($model->event_id);
            }
        });
    }

    /**
     * The number of available places (places not yet sold).
     * 
     * @return Attribute
     */
    protected function available(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->places - $this->taken;
            }
        );
    }

    /**
     * Determine the discount value for the event based on it's start_date
     * 
     * @param  integer $eventId
     * @return mixed
     */
    public static function determineDiscount(int $eventId): mixed
    {
        try {
            $event = Event::with('eventCategories')
                ->whereHas('eventCategories', function ($query) {
                    $query->whereHas('site', function ($query) {
                        $query->makingRequest();
                    });
                })->findOrFail($eventId);

            $now = Carbon::now();

            $startDate = Carbon::parse($event->eventCategories()->first()?->start_date); // Get any start_date of the event

            if ($now > $startDate) return 0; // The event is on going or has passed

            $diffInDays = $now->diffInDays($startDate);

            if ($diffInDays <= 30) {
                $discount = 30;
            } elseif (30 < $diffInDays && $diffInDays <= 90) {
                $discount = 10;
            } else {
                $discount = 0;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $discount = null;
        }

        return $discount;
    }

    /**
     * Determine the number of places a given charity has given out on the market place for a particular event
     * 
     * @param  int $charityId
     * @param  int $eventId
     * @return int
     */
    public static function charitySoldPlaces(Charity $charity, Event $event): int
    {
        $soldPlaces = static::where('charity_id', $charity->id)
            ->where('event_id', $event->id)
            ->value('taken');

        return (int) $soldPlaces;
    }

    /**
     * Determine the number of places a given charity has obtained from the market place for a particular event
     * 
     * @param  Charity $charity
     * @param  Event   $event
     * @return int
     */
    public static function charityBoughtPlaces(Charity $charity, Event $event): int
    {
        $resalePlaces = static::with(['resaleRequests' => function($query) use ($charity) {
            $query->where('charity_id', $charity->id);
            $query->where('state', ResaleRequestStateEnum::Paid);
        }])->where('charity_id', '!=', $charity->id)
            ->where('event_id', $event->id)
            ->whereHas('resaleRequests', function($query) use ($charity) {
                $query->where('charity_id', $charity->id);
                $query->where('state', ResaleRequestStateEnum::Paid);
            })->get();

        $boughtPlaces = 0;

        foreach ($resalePlaces as $resalePlace) {
            foreach ($resalePlace->resaleRequests as $resaleRequest) {
                $boughtPlaces += $resaleRequest->places;
            }
        }

        return $boughtPlaces;
    }
}