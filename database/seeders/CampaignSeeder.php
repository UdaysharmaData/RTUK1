<?php

namespace Database\Seeders;

use DB;
use Schema;
use Database\Traits\FormatDate;
use Illuminate\Database\Seeder;
use App\Enums\CampaignStatusEnum;
use App\Enums\CampaignPackageEnum;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\Campaign;
use App\Modules\Charity\Models\CampaignEvent;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CampaignSeeder extends Seeder
{
    use FormatDate, EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncateTables();

        Log::channel('dataimport')->debug('The campaign seeder logs');

        $campaigns = DB::connection('mysql_2')->table('campaigns')->get();

        foreach ($campaigns as $campaign) {
            $charity = Charity::find($campaign->charity_id);

            // Create the campaign
            $_campaign = Campaign::factory()
                ->for($charity ?? Charity::factory()->create(['id' => $campaign->charity_id]))
                ->create([
                    'id' => $campaign->id,
                    // 'user_id' => $campaign->user_id,
                    'user_id' => null,
                    'title' => $campaign->title,
                    // 'package' => $this->valueOrDefault($campaign->package, CampaignPackageEnum::Classic),
                    'package' => $this->valueOrDefault($campaign->package, '25_leads'),
                    'status' => $this->valueOrDefault($campaign->status, CampaignStatusEnum::Created),
                    'start_date' => $this->dateOrNull($campaign->start_date),
                    'end_date' => $this->dateOrNull($campaign->end_date),
                    'notification_trigger' => $this->valueOrDefault($campaign->notification_trigger),
                ]);

            if (!$charity) {
                Log::channel('dataimport')->debug("id: {$campaign->id} The charity id  {$campaign->charity_id} did not exists and was created");
            }

            if ($campaign->events) { // Create the campaign events
                foreach (json_decode($campaign->events) as $event_id) {
                    $event = Event::find($event_id);

                    CampaignEvent::factory()
                        ->for($_campaign)
                        ->for($event ?? Event::factory()->create(['id' => $event_id]))
                        ->create();

                    if (!$event) {
                        Log::channel('dataimport')->debug("id: {$campaign->id} The event id  {$event_id} did not exists and was created. Campaign: ".json_encode($campaign));
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
    public function truncateTables()
    {
        Schema::disableForeignKeyConstraints();
        Campaign::truncate();
        CampaignEvent::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
