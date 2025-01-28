<?php

namespace Database\Factories\Modules\Charity\Models;

use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
use App\Enums\CharityCharityListingTypeEnum;
use App\Modules\Charity\Models\CharityListing;
use App\Modules\Charity\Models\CharityCharityListing;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CharityCharityListing>
 */
class CharityCharityListingFactory extends CustomFactory
{
    /**
     * Reset the type but this time with the TwoYear option among the options.
     * Only two_year charities whose url (custom url) has been set are saved in the database. Those whose urls have not been set are loaded during runtime (check the CharitListing model twoYearCharities method to see how they are loaded). This helps to reduce the size of the database.
     * Go to the charity_listings & charity_charity_listings migration files for more explanation.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (CharityCharityListing $ccl) {
            if ($ccl->charityListing->show_2_year_members && $ccl->url) { // Add the TwoYear option
                // $ccl->type = $this->faker->randomElement(CharityCharityListingTypeEnum::cases()); // comment this line when running the CharityListingSeeder as it will affect the integrity of the data.
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
        return [
            'charity_listing_id' => CharityListing::factory(),
            // 'event_page_id' => $this->faker->randomElement([EventPage::factory(), null]),
            'charity_id' => Charity::factory(),
            'type' => $this->faker->randomElement([CharityCharityListingTypeEnum::PrimaryPartner, CharityCharityListingTypeEnum::SecondaryPartner]),
            'url' => $this->faker->randomElement([null, null, $this->faker->url()])
        ];
    }
}
