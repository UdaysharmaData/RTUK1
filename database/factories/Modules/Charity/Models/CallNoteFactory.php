<?php

namespace Database\Factories\Modules\Charity\Models;

use App\Enums\FaqCategoryTypeEnum;
use App\Enums\CallNoteStatusEnum;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\CallNote;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CallNote>
 */
class CallNoteFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $notes = [
            '19/11 - They have renewed. All info sent. Invoice 01/12',
            '19/11 - Had a zoom meeting with Freddie & Steph. Went really well. She can see the benefit. renewal info sent.',
            '19/11 - Only managed contact by email. Does not want a call. Asked for info which has been sent',
            '19/11 - Call booked in for the 24/11. Renewed send invoice 01/12',
            '20/11 - Donna is promoting the Santa Dash.',
            'Emailed waiting for reply . Will try mobile again.',
            'Just putting the pages together for regional.',
            'Emailed to set up zoom meeting with myself & Marc',
            'Emailed Bob to arrange zoom meeting with myself & Marc',
            'They cannot pay for the invoice. The charity will be closing.'
        ];

        return [
            'charity_id' => Charity::factory(),
            'year' => $this->faker->year($max = \Carbon\Carbon::now()),
            'call' => $this->faker->randomElement(array_keys(CallNote::$callOptions)),
            'note' => $this->faker->randomElement($notes),
            'status' => $this->faker->randomElement(CallNoteStatusEnum::cases())
        ];
    }
}
