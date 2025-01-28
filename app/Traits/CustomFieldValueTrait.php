<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Modules\Event\Models\EventCustomField;
use App\Modules\Participant\Models\Participant;

trait CustomFieldValueTrait
{
    /**
     * @param  Participant $participant
     * @param  EventCustomField $ecf
     * @return ?string
     */
    private function getCustomFieldValue(Participant $participant, EventCustomField $ecf): ?string
    {
        if ($ecf->slug == 'family_registrations') {
            $familyRegistrations = null;

            // foreach ($participant->familyRegistrations as $key => $family) {
            //     $key += 1;
            //     $name = $family['full_name'];
            //     $gender = ucfirst($family['gender']);
            //     $dob = Carbon::parse($family['dob'])->toFormattedDateString();
            //     $familyRegistrations .=
            //         "$key. [Name]: $name, [Gender]: $gender, [Date of Birth]: $dob" . PHP_EOL
            //     ;
            // }

            return $familyRegistrations ?? 'N/A';
        }

        return $participant->participantCustomFields()->where('event_custom_field_id', $ecf->id)->value('value') ?? 'N/A';
    }
}
