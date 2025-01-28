<?php

namespace Database\Seeders;

use DB;
use Schema;
use App\Enums\GenderEnum;
use Database\Traits\SiteTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Event\Models\EventThirdParty;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Event\Models\EventEventCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ExternalEnquirySeeder extends Seeder
{
    use EmptySpaceToDefaultData, SiteTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The external enquiry seeder logs');

        $this->truncateTable();

        $enquiries = DB::connection('mysql_2')->table('external_enquiries')->get();

        foreach ($enquiries as $enquiry) {
            $charity = Charity::find($enquiry->charity_id);

            $event = Event::where('name', $enquiry->event_name)
                ->orderByDesc(
                    EventEventCategory::select('start_date')
                        ->whereColumn('event_id', 'events.id')
                        ->orderByDesc('start_date')
                        ->limit(1)
                    )->first();

            $_event = $event ?? Event::factory()->create(['name' => $enquiry->event_name]); // Create the event

            $partnerChannel = PartnerChannel::where('code', $enquiry->referrer)
                ->whereHas('partner', function ($query) {
                    $query->whereHas('site', function ($query) {
                        $query->where('id', static::getSite()?->id);
                    });
                })->first();

            $partnerChannel = $partnerChannel ?? PartnerChannel::factory()->create(['name' => $enquiry->referrer, 'code' => $enquiry->referrer]);

            $names = explode(' ', $enquiry->fullname, 1);
            $firstName = $names[0];
            $lastName = isset($names[1]) ? $names[1] : null;

            ExternalEnquiry::factory()
                ->for($_event)
                ->for($partnerChannel)
                ->for($charity ?? Charity::factory()->create(['id' => $enquiry->charity_id]))
                ->create([
                    // 'status' => $this->valueOrDefault($enquiry->status) ? $enquiry->status : ExternalEnquiryStatusEnum::Pending,
                    // 'participant_id' => null // TODO: Map the enquiry to the participant and set the paritcipant_id. Rely on the participant first_namd & last_name and the event name to get the participant_id associated to this enquiry
                    'contacted' => $enquiry->made_contact,
                    'converted' => $enquiry->converted,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $enquiry->email,
                    'phone' => $enquiry->phone,
                    'gender' => $this->valueOrDefault($enquiry->sex, GenderEnum::Male),
                    'postcode' => $enquiry->postcode,
                    'comments' => $enquiry->comments,
                    'timeline' => json_decode($enquiry->timeline),
                    'token' => $enquiry->token
                ]);

            if (!$charity) {
                Log::channel('dataimport')->debug("id: {$enquiry->id} The charity id {$enquiry->charity_id} did not exists and was created. External_enquiry: ".json_encode($enquiry));
            }

            if (!$event) {
                Log::channel('dataimport')->debug("id: {$enquiry->id} The event name {$enquiry->event_name} did not exists and was created. External_enquiry: ".json_encode($enquiry));
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
        ExternalEnquiry::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
