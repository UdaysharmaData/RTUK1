<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Enquiry\Models\CharityEnquiry;
use App\Modules\Charity\Models\CharityCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CharityEnquirySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity enquiry seeder logs');

        $this->truncateTable();

        $charityEnquiries = DB::connection('mysql_2')->table('charity_signups')->get();

        foreach ($charityEnquiries as $enquiry) {
            $category = CharityCategory::where('name', 'like', '%'.$enquiry->sector.'%')->first();

            CharityEnquiry::factory()
                ->for($category ?? CharityCategory::factory()->create(['name' => $enquiry->sector]))
                ->create([
                    'name' => $enquiry->name,
                    'registration_number' => $enquiry->number,
                    'website' => $enquiry->website,
                    'address_1' => $enquiry->address_1,
                    'address_2' => $enquiry->address_2,
                    'city' => $enquiry->city,
                    'postcode' => $enquiry->postcode,
                    'contact_name' => $enquiry->contact_name,
                    'contact_email' => $enquiry->contact_email,
                    'contact_phone' => $enquiry->contact_phone,
                ]);

            if (!$category) {
                Log::channel('dataimport')->debug("id: {$enquiry->id} The sector (charity category id) {$enquiry->sector} did not exists and was created. Charity_enquiry: ".json_encode($enquiry));
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
        CharityEnquiry::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
