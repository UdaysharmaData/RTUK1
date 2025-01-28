<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Enquiry\Models\PartnerEnquiry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PartnerEnquirySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncateTable();

        Log::channel('dataimport')->debug('The partner enquiry seeder logs');

        $enquiries = DB::connection('mysql_2')->table('partner_signups')->get();

        foreach ($enquiries as $enquiry) {
            PartnerEnquiry::factory()
                ->create([
                    'name' => $enquiry->company_name,
                    'website' => $enquiry->website,
                    'information' => $enquiry->information,
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
        PartnerEnquiry::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
