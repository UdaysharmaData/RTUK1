<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Enums\CampaignLeadChannelEnum;
use App\Modules\Charity\Models\Campaign;
use App\Modules\Charity\Models\CampaignLead;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CampaignLeadSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The campaign lead seeder logs');

        $this->truncateTable();

        $campaignLeads = DB::connection('mysql_2')->table('campaign_leads')->get();

        foreach ($campaignLeads as $lead) {
            $campaign = Campaign::find($lead->campaign_id);

            CampaignLead::factory()
                ->for($campaign ?? Campaign::factory()->create(['id' => $lead->campaign_id]))
                ->create([
                    'channel' => $this->valueOrDefault($lead->channel, CampaignLeadChannelEnum::RunThroughMax),
                    'count' => $lead->count,
                    'threshold' => $lead->threshold,
                    'notification_trigger' => $lead->notification_trigger
                ]);

            if (!$campaign) {
                Log::channel('dataimport')->debug("id: {$lead->id}  The campaign id  {$lead->campaign_id} did not exists and was created. Campaign_lead: ".json_encode($lead));
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
        CampaignLead::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
