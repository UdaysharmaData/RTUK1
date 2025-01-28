<?php

namespace App\Services\ExportManager\Formatters;

use Carbon\Carbon;
use App\Http\Helpers\TextHelper;
use App\Modules\Event\Models\Event;
use App\Enums\ListSoftDeletedItemsOptionsEnum;
use App\Services\ExportManager\Interfaces\ExportableDataTemplateInterface;

class EventExportableDataFormatter implements ExportableDataTemplateInterface
{
    public function format(mixed $list): array
    {
        $data = [];

        foreach ($list as $event) {
            $temp['name'] = $event->name;
            $temp['slug'] = $event->slug;
            $temp['categories'] = $event->eventCategories->count() ? implode(', ', $event->eventCategories->sortBy('name')->pluck('name')->toArray()) : null;
            $temp['partner'] = $event->partner_event ? 'True' : 'False';
            $temp['location'] = $event->address?->address;
            $temp['latitude'] = $event->address?->latitude;
            $temp['longitude'] = $event->address?->longitude;
            $temp['venue'] = $event->venue?->name;
            $temp['city'] = $event->city?->name;
            $temp['region'] = $event->region?->name;
            $temp['postcode'] = $event->postcode;
            $temp['country'] = $event->country;
            $temp['website'] = $event->website;
            $temp['category'] = $this->getCategoriesValue($event);
            $temp['fundraising_emails'] = $event->fundraising_emails ? 'Enabled' : 'Disabled';
            $temp['archived'] = $event->archived ? 'True' : 'False';
            $temp['charity_checkout_integration'] = $event->charity_checkout_integration ? 'Enabled' : 'Disabled';
            $temp['type'] = $event->type->name;

            if (request()->filled('deleted') && (request()->filled('deleted') == ListSoftDeletedItemsOptionsEnum::With->value || request()->filled('deleted') == ListSoftDeletedItemsOptionsEnum::Only->value)) 
                $temp['deleted'] = $event->deleted_at?->toDayDateTimeString();

            // Purify the HTML text
            $temp['description'] = TextHelper::purify($event->description);

            $data[] = $temp;
        }

        return $data;
    }

    /**
     * Format the value of the categories field
     *
     * @param  Event $event
     * @return ?string
     */
    private function getCategoriesValue(Event $event): ?string
    {
        if ($event->eventCategories->count()) {

            $categories = null;
            $numbering = 1;

            foreach ($event->eventCategories->sortBy('name') as $key => $category) {
                $name = $category['name'];
                $startDate = $category['pivot']['start_date'] ? Carbon::parse($category['pivot']['start_date'])->toFormattedDateString() : 'N/A';
                $endDate = $category['pivot']['end_date'] ? Carbon::parse($category['pivot']['end_date'])->toFormattedDateString() : 'N/A';
                $registrationDeadline = $category['pivot']['registration_deadline'] ? Carbon::parse($category['pivot']['registration_deadline'])->toFormattedDateString() : 'N/A';
                $withdrawalDeadline = $category['pivot']['withdrawal_deadline'] ? Carbon::parse($category['pivot']['withdrawal_deadline'])->toFormattedDateString() : 'N/A';
                $localFee = $category['pivot']['local_fee'];
                $internationalFee = $category['pivot']['international_fee'];
                $totalPlaces = $category['pivot']['total_places'];

                $categories .=
                    "$numbering. [Name]: $name, [Start Date]: $startDate, [End Date]: $endDate, [Registration Deadline]: $registrationDeadline, [Withdrawal Deadline]: $withdrawalDeadline, [Local Fee]: $localFee, [International Fee]: $internationalFee, [Total Places]: $totalPlaces" . PHP_EOL
                ;

                if ($category['pivot']['classic_membership_places']) { // TODO: Update this if statement to instead check if the site works with charities
                    $classicMembershipPlaces = $category['pivot']['classic_membership_places'];
                    $premiumMembershipPlaces = $category['pivot']['premium_membership_places'];
                    $twoYearMembershipPlaces = $category['pivot']['two_year_membership_places'];

                    $categories .=
                        "[Classic Membership Places]: $classicMembershipPlaces, [Premium Membership Places]: $premiumMembershipPlaces, [Two Year Membership Places]: $twoYearMembershipPlaces" . PHP_EOL
                    ;

                }
            
                $numbering += 1;
            }

            return $categories ?? 'N/A';
        }

        return 'N/A';
    }
}