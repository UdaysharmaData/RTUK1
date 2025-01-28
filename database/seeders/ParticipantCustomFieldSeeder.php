<?php

namespace Database\Seeders;

use DB;
use Str;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Event\Models\EventCustomField;
use App\Modules\Participant\Models\Participant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Modules\Participant\Models\ParticipantCustomField;

class ParticipantCustomFieldSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event custom field seeder logs');

        $this->truncateTable();

        $customFields = DB::connection('mysql_2')->table('participant_custom_fields')->get();

        foreach ($customFields as $field) {
            $participant = Participant::find($field->participant_id);
            $eventCustomField = EventCustomField::find($field->event_custom_field_id);

            if ($field->event_custom_field_id) { // Only create the event custom field is the id is > 0
                if ($field->key == 'cf_passport_number') { // Passport number is not considered as a custom field anymore. It's now a optional field
                    if (! $participant) {
                        $participant = Participant::factory()->create(['id' => $field->participant_id]);
                    }

                    // Event::where('id', $eventCustomField->event_id)->update(['reg_passport_number' => true]); // TODO: It is not efficient to have this line here.

                    if (! $participant->user) {
                        $participant->user()->create([
                            'id' => $participant->user_id,
                            'email' => Str::random(16).'@gmail.com',
                            'first_name' => Str::random(16),
                            'last_name' => Str::random(16)
                        ]);
                    
                        Log::channel('dataimport')->debug("id: {$field->id} The user id {$field->user_id} did not exists and was created. Participant_custom_field: ".json_encode($field));
                    }

                    if (! $participant->user->profile) {
                        $participant->user->profile()->create([
                            'user_id' => $participant->user->id
                        ]);
                    
                        Log::channel('dataimport')->debug("id: {$field->id} The user's profile did not exists and was created. Participant_custom_field: ".json_encode($field));
                    }

                    $participant->user->profile()->update([
                        'passport_number' => $field->value
                    ]);
                } else {
                    ParticipantCustomField::factory()
                        ->for($participant ?? Participant::factory()->create(['id' => $field->participant_id]))
                        ->for($eventCustomField ?? EventCustomField::factory()->create(['id' => $field->event_custom_field_id]))
                        ->create([
                            'id' => $field->id,
                            'value' => $this->valueOrDefault($field->value)
                        ]);

                    if (!$participant) {
                        Log::channel('dataimport')->debug("id: {$field->id} The participant id {$field->participant_id} did not exists and was created. Participant_custom_field: ".json_encode($field));
                    }

                    if (!$eventCustomField) {
                        Log::channel('dataimport')->debug("id: {$field->id} The event custom field id {$field->event_custom_field_id} did not exists and was created. Participant_custom_field: ".json_encode($field));
                    }
                }
            }
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
        ParticipantCustomField::truncate();
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Get the event
     * 
     * @param  string  $options
     * @param  string  $values
     * @return ?array
     */
    private function getResponseOptions(string $options, string $values): ?array
    {
        $options = explode(',', $options);
        $values = explode(',', $values);

        $result = [
            'options' => [],
            'values' => [],
        ];

        foreach ($options as $key => $option) {
            $result = [
                'options' => [...$result['options'], $option],
                'values'  => [...$result['values'], $values[$key] ?? null], // TODO: £ gets truncated to \u00a320. Fix it by removing £.
            ];
        }

        return $result;
    }
}
