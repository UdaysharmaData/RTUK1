<?php

namespace Database\Seeders;

use Database\Traits\SiteTrait;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeploymentParticipantSeeder extends Seeder
{
    use /*WithoutModelEvents,*/ SiteTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Modules\Participant\Models\Participant::factory()->count(100000)->create();
        \App\Modules\Participant\Models\ParticipantAction::factory()->count(100000)->create();
        \App\Modules\Participant\Models\ParticipantCustomField::factory()->count(100000)->create();
        \App\Modules\Participant\Models\ParticipantExtra::factory()->count(100000)->create();
    }
}
