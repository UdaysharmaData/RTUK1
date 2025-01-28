<?php

namespace App\Services\ExportManager\Formatters;

use App\Modules\Setting\Enums\SiteEnum;
use App\Traits\SiteTrait;
use App\Modules\Setting\Enums\OrganisationEnum;
use App\Services\ExportManager\Interfaces\ExportableDataTemplateInterface;

class EnquiryExportableDataFormatter implements ExportableDataTemplateInterface
{
    use SiteTrait;

    public function format(mixed $list): array
    {
        $data = [];

        foreach ($list as $enquiry) {
            if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) { // TODO: Create a helper that checks whether the site making the request works with charity or not and returns bool.
                $temp['charity'] = $enquiry->charity?->name;
            }

            $temp['event'] = $enquiry->event?->name;
            $temp['event_category'] = $enquiry->eventCategory?->name;

            if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) { // TODO: Create a helper that checks whether the site making the request works with charity or not and returns bool.
                $temp['corporate'] = $enquiry->corporate?->name;
            }

            $temp['external_enquiry'] = $enquiry->external_enquiry_id ? true : false;
            $temp['first_name'] = $enquiry->first_name;
            $temp['last_name'] = $enquiry->last_name;
            $temp['email'] = $enquiry->email;
            $temp['phone'] = $enquiry->phone;
            $temp['gender'] = $enquiry->gender?->name;
            $temp['postcode'] = $enquiry->postcode;
            $temp['contacted'] = $enquiry->contacted;
            $temp['converted'] = $enquiry->converted;
            $temp['participant'] = $enquiry->participant_id ? true : false;
            $temp['comments'] = $enquiry->comments;
            $temp['timeline'] = $enquiry->timeline;
            $temp['fundraising_target'] = $enquiry->fundraising_target;

            if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) { // TODO: Create a helper that checks whether the site making the request works with charity or not and returns bool.
                $temp['custom_charity'] = $enquiry->custom_charity;
            }

            $data[] = $temp;
        }

        return $data;
    }
}
