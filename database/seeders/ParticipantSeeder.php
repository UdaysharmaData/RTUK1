<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Modules\Charity\Models\Charity;
use App\Modules\Corporate\Models\Corporate;
use App\Modules\Event\Models\EventCategory;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ParticipantSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The participants seeder logs');

        $this->truncateTable();

        $participants = DB::connection('mysql_2')->table('participants')->get();
        
        foreach ($participants as $participant) {
            $user = User::find($participant->user_id);
            $event = Event::find($participant->event_id);
            if ($event && isset($event->eventCategories[0])) {
                // $eventCategory = $event->eventCategories[0]; // Currently, for all the seeded data, an event has only one event category.
                $eec = $event->eventCategories[0]->pivot;
            } else { // Create the event and associate it to a default event category.
                if (! $event) {
                    $_event = Event::factory()->create(['id' => $participant->event_id]);
                    $event = $_event;
                }

                $eventCategory = EventCategory::inRandomOrder()->whereHas('site', function ($query) {
                    $query->makingRequest();
                })->first();

                $eec = EventEventCategory::factory()->create([
                    'event_id' => $event->id,
                    'event_category_id' => $eventCategory?->id
                ]);

                if (isset($_event)) {
                    Log::channel('dataimport')->debug("id: {$participant->id}  The event id {$event->id} did not exists and was created and associated to the event category {$eventCategory?->id}. Participant: ".json_encode($participant));
                } else {
                    Log::channel('dataimport')->debug("id: {$participant->id}  The event id {$event->id} existed but was not associated to an event category. The event got associated to the event category {$eventCategory?->id}. Participant: ".json_encode($participant));
                }
            }

            // $corporate = Corporate::find($participant->corporate_id);
            $foreignKeyColumns = [];

            $_participant = Participant::factory();

            if ($participant->charity_id) { // check if the charity exists
                $charity = Charity::find($participant->charity_id);
                $_participant = $_participant->for($charity ?? Charity::factory()->create(['id' => $participant->charity_id]));

                if (!$charity) {
                    Log::channel('dataimport')->debug("id: {$participant->id} The charity id {$participant->charity_id} did not exists and was created. Participant: ".json_encode($participant));
                }
            } else {
                $foreignKeyColumns = ['charity_id' => null, ...$foreignKeyColumns];
            }

            $_user = $user ?? User::factory()->create(['id' => $participant->user_id]);

            $_participant = $_participant->for($eec)
                // ->for($corporate ?? Corporate::factory()->create(['id' => $participant->corporate_id]))
                ->for($_user)
                ->create([
                    ...$foreignKeyColumns,
                    'id' => $participant->id,
                    'corporate_id' => null,
                    'status' => $participant->status == 'paid' ? ParticipantStatusEnum::Notified : $this->valueOrDefault($participant->status, ParticipantStatusEnum::Notified),
                    'waive' => $this->getWaiveValue($participant),
                    'waiver' => $this->getWaiverValue($participant),
                    'added_via' => $this->getAddedViaValue($participant->added_by)
                ]);

            if ($this->valueOrDefault($participant->mobile)) { // Update user's mobile number
                $_participant->user()->update([
                    'phone' => $participant->mobile
                ]);
            }

            if ($this->valueOrDefault($participant->contact_number)) { // Update user's phone/contact number
                $_participant->user()->update([
                    'phone' => $participant->contact_number
                ]);
            }

            if (!$user) {
                Log::channel('dataimport')->debug("id: {$participant->id}  The user id  {$participant->user_id} did not exists and was created. Participant: ".json_encode($participant));
            }
 
            if (!$event) {
                Log::channel('dataimport')->debug("id: {$participant->id}  The event id  {$participant->event_id} did not exists and was created. Participant: ".json_encode($participant));
            }

            // if (!$corporate) {
            //     Log::channel('dataimport')->debug("id: {$participant->id}  The corporate id  {$participant->corporate_id} did not exists and was created. Participant: ".json_encode($participant));
            // }

            // TODO: Implement a method to set/update the participants dob on the Profile model
        }
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        Participant::truncate();
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Get the waive value of the participant
     * 
     * @param  object  $participant
     * @return ?string
     */
    private function getWaiveValue(object $participant): ?string
    {
        $value = null;

        if ($participant->exempt) {
            $value =  ParticipantWaiveEnum::Completely->value;

        } else if ($participant->partial_exempt) {
            $value = ParticipantWaiveEnum::Partially->value;
        }

        return $value;
    }

    /**
     * Get the waiver value of the participant
     * 
     * @param  object  $participant
     * @return ?string
     */
    private function getWaiverValue(object $participant): ?string
    {
        $value = null;

        if ($participant->exempt) {
            $value =  ParticipantWaiverEnum::Charity->value;

        } else if ($participant->partial_exempt) {
            $value = ParticipantWaiverEnum::Charity->value;

        } else if ($participant->corporate_exempt) {
            $value = ParticipantWaiverEnum::Corporate->value;

        } else if ($participant->external_exempt) {
            $value = ParticipantWaiverEnum::Partner->value;
        }

        return $value ?? null;
    }

    /**
     * Get the added via value of the participant
     * 
     * @param  ?string  $value
     * @return string
     */
    private function getAddedViaValue(?string $value): string
    {
        switch ($value) {
            case 'virtual_event_team_invitation':
                $value = ParticipantAddedViaEnum::TeamInvitation->value;
                break;

            case 'virtual_event_dashboard':
                $value = ParticipantAddedViaEnum::PartnerEvents->value;
                break;

            case ParticipantAddedViaEnum::ExternalEnquiryOffer->value:
                $value = ParticipantAddedViaEnum::ExternalEnquiryOffer->value;
                break;

            case ParticipantAddedViaEnum::RegistrationPage->value:
                $value = ParticipantAddedViaEnum::RegistrationPage->value;
                break;

            case 'vms_website':
                $value = ParticipantAddedViaEnum::Website->value;
                break;

            case 'charity':
                $value = ParticipantAddedViaEnum::PartnerEvents->value;
                break;
    
            default:
                $value = ParticipantAddedViaEnum::PartnerEvents->value;
        }

        return $value;
    }
}
