<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Enquiry\Models\EventEnquiry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventEnquirySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event enquiry seeder logs');

        $this->truncateTable();

        $enquiries = DB::connection('mysql_2')->table('event_signups')->get();
        
        foreach ($enquiries as $enquiry) {
            EventEnquiry::factory()
                ->create([
                    'name' => $enquiry->name,
                    'distance' => $enquiry->distance,
                    'entrants' => $enquiry->entrants,
                    'website' => $enquiry->website,
                    'address_1' => $enquiry->address_1,
                    'address_2' => $enquiry->address_2,
                    'city' => $enquiry->city,
                    'postcode' => $enquiry->postcode,
                    'contact_name' => $enquiry->contact_name,
                    'contact_email' => $enquiry->contact_email,
                    'contact_phone' => $enquiry->contact_phone
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
        EventEnquiry::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
