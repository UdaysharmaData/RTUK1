<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use App\Enums\EnquiryStatusEnum;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Charity\Models\Charity;
use App\Modules\Corporate\Models\Corporate;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EnquirySeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The enquiry seeder logs');

        $this->truncateTable();

        $enquiries = DB::connection('mysql_2')->table('signups')->get();

        foreach ($enquiries as $enquiry) {
            $foreignKeyColumns = [];

            $_enquiry = Enquiry::factory();

            if ($enquiry->charity_id) { // check if the charity exists
                $charity = Charity::find($enquiry->charity_id);
                $_enquiry = $_enquiry->for($charity ?? Charity::factory()->create(['id' => $enquiry->charity_id]));

                if (!$charity) {
                    Log::channel('dataimport')->debug("id: {$enquiry->id} The charity id  {$enquiry->charity_id} did not exists and was created. Enquiry: ".json_encode($enquiry));
                }
            } else {
                $foreignKeyColumns = ['charity_id' => null];
            }

            if ($enquiry->event_id) { // check if the event exists
                if ($enquiry->event_id < 0) {
                    Log::channel('dataimport')->debug('id:'. $enquiry->id .' The event id is '.$enquiry->event_id.' (Probably a virtual participant enquiry - look at the VirtualFundraisingController updateCharity method (where it is set). The event_id was set to null and the record saved.');
                    $foreignKeyColumns = ['event_id' => null, ...$foreignKeyColumns];
                } else {
                    $event = Event::find($enquiry->event_id);
                    $_enquiry = $_enquiry->for($event ?? Event::factory()->create(['id' => $enquiry->event_id]));
                }

                if (!$event) {
                    Log::channel('dataimport')->debug("id: {$enquiry->id} The event id  {$enquiry->event_id} did not exists and was created. Enquiry: ".json_encode($enquiry));
                }
            } else {
                $foreignKeyColumns = ['event_id' => null, ...$foreignKeyColumns];
            }

            if ($enquiry->corporate_id) { // check if the corporate exists
                $corporate = Corporate::find($enquiry->corporate_id);
                $_enquiry = $_enquiry->for($corporate ?? Corporate::factory()->create(['id' => $enquiry->corporate_id]));

                if (!$corporate) {
                    Log::channel('dataimport')->debug("id: {$enquiry->id} The corporate id  {$enquiry->corporate_id} did not exists and was created. Enquiry: ".json_encode($enquiry));
                }
            } else {
                $foreignKeyColumns = ['corporate_id' => null, ...$foreignKeyColumns];
            }

            if ($enquiry->site_id) { // check if the site exists
                $site = Site::find($enquiry->site_id);
                $_enquiry = $_enquiry->for($site ?? Site::factory()->create(['id' => $enquiry->site_id]));

                if (!$site) {
                    Log::channel('dataimport')->debug("id: {$enquiry->id} The site id  {$enquiry->site_id} did not exists and was created. Enquiry: ".json_encode($enquiry));
                }
            } else {
                $foreignKeyColumns = ['site_id' => null, ...$foreignKeyColumns];
            }

            $_enquiry = $_enquiry->create([
                ...$foreignKeyColumns,
                'name' => $enquiry->fullname,
                'email' => $enquiry->email,
                'phone' => $this->valueOrDefault($enquiry->phone),
                'sex' => $this->valueOrDefault($enquiry->sex),
                'postcode' => $this->valueOrDefault($enquiry->postcode),
                'status' => $this->valueOrDefault($enquiry->status, EnquiryStatusEnum::Processed),
                'made_contact' => $enquiry->made_contact,
                'converted' => $enquiry->converted,
                'comments' => $enquiry->comments,
                'custom_charity' => $enquiry->custom_charity,
                'fundraising_target' => $this->valueOrDefault($enquiry->how_much_raise)
            ]);
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
        Enquiry::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
