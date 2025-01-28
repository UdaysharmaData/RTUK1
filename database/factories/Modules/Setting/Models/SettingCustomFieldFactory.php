<?php

namespace Database\Factories\Modules\Setting\Models;

use Database\Factories\CustomFactory;
use App\Modules\Setting\Models\Setting;
use App\Enums\SettingCustomFieldTypeEnum;
use App\Modules\Setting\Models\SettingCustomField;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Setting\Models\SettingCustomField>
 */
class SettingCustomFieldFactory extends CustomFactory
{
    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (SettingCustomField $settingCustomField) {
            if ($settingCustomField->key == 'classic_membership_default_places' || $settingCustomField->key == 'premium_membership_default_places' || $settingCustomField->key == 'two_year_membership_default_places') {
                $settingCustomField->type = SettingCustomFieldTypeEnum::AllEvents;
            }

            if ($settingCustomField->key == 'partner_membership_default_places') { // Ensure charities having the partner membership type only have 1 place and it is for all events.
                $settingCustomField->value = 1;
                $settingCustomField->type = SettingCustomFieldTypeEnum::AllEvents;
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
        $keys = ['classic_membership_default_places', 'premium_membership_default_places', 'two_year_membership_default_places', 'partner_membership_default_places', 'classic_renewal', 'new_classic_renewal', 'premium_renewal, new_premium_renewal', 'two_year_renewal', 'new_two_year_renewal', 'partner_renewal', 'new_partner_renewal'];
        $values = [1, 5, 20, 750, 1000, 1200, 1800, 2500];

        return [
            'setting_id' => Setting::factory(),
            'key' => $this->faker->randomElement($keys),
            'value' => $this->faker->randomElement($values),
            'type' => $this->faker->randomElement(SettingCustomFieldTypeEnum::cases())
        ];
    }
}
