<?php

namespace App\Services\ExportManager\Formatters;

use App\Http\Helpers\AccountType;
use App\Traits\CustomFieldValueTrait;
use App\Enums\ListSoftDeletedItemsOptionsEnum;
use App\Modules\Participant\Models\Participant;
use App\Services\ExportManager\Interfaces\ExportableDataTemplateInterface;

class ParticipantExportableDataFormatter implements ExportableDataTemplateInterface
{
    use CustomFieldValueTrait;

    public function format(mixed $list): array
    {
        $data = [];

        foreach ($list as $participant) {
            $temp['status'] = $participant->status?->name;
            $temp['first_name'] = $participant->user?->first_name;
            $temp['last_name'] = $participant->user?->last_name;
            $temp['gender'] = $participant->user?->profile?->gender?->name ?? 'N/A';
            $temp['email'] = $participant->user?->email ?? 'N/A';
            $temp['event'] = $participant->event->name ?? 'N/A';
            $temp['charity'] = $participant->charity?->name ?? 'N/A';

            $_participant = Participant::withTrashed()
                ->find($participant->id);
            // $_participant->load('familyRegistrations');

            $temp['added_via'] = Participant::getAddedVia($_participant);

            if (isset($participant->event->eventCustomFields)) {
                foreach ($participant->event->eventCustomFields as $ecf) {
                    $temp[$ecf->slug] = $this->getCustomFieldValue($_participant, $ecf);
                }
            }

            $temp['address'] = $participant->user?->profile?->address;
            $temp['city'] = $participant->user?->profile?->city;
            $temp['region'] = $participant->user?->profile?->region;
            $temp['postcode'] = $participant->user?->profile?->postcode;
            $temp['state'] = $participant->user?->profile?->state;
            $temp['dob'] = $participant->user?->profile?->dob;
            $temp['nationality'] = $participant->user?->profile?->nationality;
            $temp['country'] = $participant->user?->profile?->country;
            $temp['contact_number'] = $participant->user?->phone;
            $temp['registration_date'] = $participant->created_at;

            if (! (AccountType::isCharityOwnerOrCharityUser() || AccountType::isAccountManager() || AccountType::isEventManager())) {
                $temp['waive'] = $participant->waive?->name;
                $temp['waiver'] = $participant->waiver?->name;
                // $temp['charge_id'] = $participant->charge_id;
                // $temp['refund_id'] = $participant->refund_id;
                // $temp['refunded'] = $participant->refunded;
                $temp['preferred_heat_time'] = $participant->preferred_heat_time;
                $temp['raced_before'] = $participant->raced_before;
                $temp['estimated_finish_time'] = $participant->estimated_finish_time;
                $temp['tshirt_size'] = $participant->user?->profile?->participantProfile?->tshirt_size?->name;
                $temp['age_on_race_day'] = $participant->age_on_race_day;
                $temp['month_born_in'] = $participant->user?->profile?->month_born_in;
                $temp['occupation'] = $participant->user?->profile?->occupation;
                $temp['emergency_contact_name'] = $participant->user?->profile?->participantProfile?->emergency_contact_name;
                $temp['emergency_contact_phone'] = $participant->user?->profile?->participantProfile?->emergency_contact_phone;

                if (request()->filled('deleted') && (request()->filled('deleted') == ListSoftDeletedItemsOptionsEnum::With->value || request()->filled('deleted') == ListSoftDeletedItemsOptionsEnum::Only->value)) 
                    $temp['deleted'] = $participant->deleted_at?->toDayDateTimeString();
            }

            $data[] = $temp;
        }

        return $data;
    }
}