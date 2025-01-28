<?php

namespace Database\Seeders;

use DB;
use Str;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Enums\EventCustomFieldRuleEnum;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Event\Models\EventCustomField;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventCustomFieldSeeder extends Seeder
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

        $customFields = DB::connection('mysql_2')->table('event_custom_fields')->get();

        foreach ($customFields as $field) {
            $event = Event::find($field->event_id);

            if ($field->name != 'Passport Number') { // Passport number is not considered as a custom field anymore. It's now a optional field
                // Checks if the slug is unique for a given event
                $exists = EventCustomField::where('event_id', $field->event_id)->where('slug', $field->slug)->exists();

                EventCustomField::factory()
                    ->for($event ?? Event::factory()->create(['id' => $field->event_id]))
                    ->create([
                        'id' => $field->id,
                        'name' => $field->name,
                        'slug' => $exists ? $field->slug. '-' .Str::random(5) : $field->slug,
                        'type' => $field->response,
                        'caption' => $field->content,
                        'possibilities' => ($field->response == 'select' && $this->valueOrDefault($field->response_options) && $this->valueOrDefault($field->response_values)) ? $this->getResponseOptions($field->response_options, $field->response_values) : null,
                        'status' => $field->status,
                        'rule' => EventCustomFieldRuleEnum::Required
                    ]);

                if (!$event) {
                    Log::channel('dataimport')->debug("id: {$field->id} The event id {$field->event_id} did not exists and was created. Event_custom_field: ".json_encode($field));
                }
            }
        
            if ($field->name == 'Passport Number') {
                Log::channel('dataimport')->debug("id: {$field->id} The passport number was not saved as a custom field as it is not a custom field anymore but an optional field. Event_custom_field: ".json_encode($field));
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
        EventCustomField::truncate();
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
