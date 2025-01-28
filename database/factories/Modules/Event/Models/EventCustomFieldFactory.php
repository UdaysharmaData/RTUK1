<?php

namespace Database\Factories\Modules\Event\Models;

use Str;
use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Enums\EventCustomFieldRuleEnum;
use App\Enums\EventCustomFieldTypeEnum;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventCustomField>
 */
class EventCustomFieldFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->randomElement(['Race Terms and Conditions', 'Waiver', 'Refund Policy', 'Terms and Conditions', 'Where did you hear about the event?', 'Family Registrations']);
        $slug = Str::slug($name);

        return [
            'event_id' => Event::factory(),
            'name' => $name,
            'slug' => $slug,
            'type' => $this->faker->randomElement(EventCustomFieldTypeEnum::cases()),
            'caption' => $this->faker->randomElement(['I accepts events <a href="//www.racespace.com/assets/uploads/events/671/waiver.pdf" target="_BLANK">Waiver</a>']),
            'possibilities' => $this->faker->randomElement([
                [
                    ['options' => ['Yes', 'No']],
                    ['values' => ['yes', 'no']]
                ],
                [
                    ['options' => ['None', '5-15']],
                    ['values' => ['none', '5-15']]
                ],
                [
                    ['options' => ['5k', '10k', '15k']],
                    ['values' => ['5k', '10k', '15k']]
                ],
            ]),
            'status' => $this->faker->boolean(95),
            'rule' => $this->faker->randomElement(EventCustomFieldRuleEnum::cases())
        ];
    }
}
