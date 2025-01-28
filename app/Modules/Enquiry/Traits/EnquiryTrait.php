<?php

namespace App\Modules\Enquiry\Traits;

use App\Enums\CharityUserTypeEnum;
use App\Modules\Charity\Models\Charity;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Enquiry\Models\ExternalEnquiry;

trait EnquiryTrait
{
    /**
     * Get the charity under which the participant is to be registered (offered a place)
     *
     * @param  Enquiry|ExternalEnquiry $enquiry
     * @return Charity|null
     */
    protected function offerCharity(Enquiry|ExternalEnquiry $enquiry): ?Charity
    {
        if ($enquiry->charity) { // Preselect the charity in the enquiry if it is present.
            $offerCharity = $enquiry->charity;
        } else if ($enquiry->user) { // Preselect the user's (participant) default charity if the user exists.
            $offerCharity = $enquiry->user->charities()->wherePivot('type', CharityUserTypeEnum::Participant)->first();
        }

        return $offerCharity ?? null;
    }

    /**
     * Determine whether the user's (participant) default charity is different from the enquiry charity.
     *
     * @param  Enquiry|ExternalEnquiry $enquiry
     * @return bool
     */
    protected function charityConflict(Enquiry|ExternalEnquiry $enquiry): bool
    {
        if ($enquiry->user && $enquiry->charity) { // Check if the user exists (if the enquiry email exists on users table) and the enquiry is associated to a charity.
            if ($charity = $enquiry->user->charities()->wherePivot('type', CharityUserTypeEnum::Participant)->first()) { // Get the default charity of the user (participant).
                if ($charity->id != $enquiry->charity->id) { // Set the variable below whenever the charity in the enquiry is different from the user's (participant) default charity.
                    $charityConflict = true;
                }
            }
        }

        return $charityConflict ?? false;
    }
}
