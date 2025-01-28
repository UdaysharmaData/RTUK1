<?php

namespace Database\Factories\Modules\Event\Models;

use Carbon\Carbon;
use Database\Traits\SiteTrait;
use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Modules\Setting\Models\Setting;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventEventCategory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventEventCategory>
 */
class EventEventCategoryFactory extends CustomFactory
{
    use SiteTrait;

    /**
     * Ensure rolling events don't have a registration_deadline nor a withdrawal_deadline.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (EventEventCategory $eventEventCategory) {
            if ($eventEventCategory->event?->type?->value == 'rolling') {
                $eventEventCategory->registration_deadline = null;
                $eventEventCategory->withdrawal_deadline = null;
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $startDate = $this->faker->dateTimeThisYear();
        $registration_deadline = $this->faker->dateTimeInInterval('-2 years');
        $withdrawal_deadline = Carbon::parse($registration_deadline)->addWeek();
        $classicMembershipDefaultPlaces = Setting::where('site_id', static::getSite()?->id)->first()?->settingCustomFields()->where('key', 'classic_membership_default_places')?->value('value');
        $premiumMembershipDefaultPlaces = Setting::where('site_id', static::getSite()?->id)->first()?->settingCustomFields()->where('key', 'premium_membership_default_places')?->value('value');
        $twoYearMembershipDefaultPlaces = Setting::where('site_id', static::getSite()?->id)->first()?->settingCustomFields()->where('key', 'two_year_membership_default_places')?->value('value');

        return [
            'event_id' => Event::factory(),
            'event_category_id' => EventCategory::factory(),
            'local_fee' => $this->faker->randomNumber(5),
            'international_fee' => $this->faker->randomNumber(5),
            'start_date' => $startDate,
            'end_date' => Carbon::parse($startDate)->addHours(rand(1, 5)),
            'registration_deadline' => $registration_deadline,
            'total_places' => $this->faker->randomNumber(5),
            'withdrawal_deadline' => $withdrawal_deadline,
            'classic_membership_places' => $classicMembershipDefaultPlaces,
            'premium_membership_places' => $premiumMembershipDefaultPlaces,
            'two_year_membership_places' => $twoYearMembershipDefaultPlaces
        ];
    }
}
