<?php

namespace Database\Seeders;

use DB;
use str;
use Schema;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Event\Models\PromotionalPage;
use App\Modules\Event\Models\EventPageListing;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PromotionalPageSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The promotional page seeder logs');

        $this->truncateTable();

        $promotionalPages = DB::connection('mysql_2')->table('promotional_pages')->get();
        
        foreach ($promotionalPages as $page) {
            $foreignKeyColumns = [];
            $epl = EventPageListing::find($page->event_page_listing_id);
            
            $_page = PromotionalPage::factory();
            
            if ($this->valueOrDefault($page->county)) {
                $regionName = ucwords(Str::replace('_', ' ', Str::replace('_-_', ' ', $page->county)));
                $region = Region::where('name', $regionName)->first();
                $_page = $_page->for($region ?? Region::factory()->create(['name' => $regionName]));
            } else {
                $foreignKeyColumns = ['region_id' => null];
            }

            $_page->for($epl ?? EventPageListing::factory()->create(['id' => $page->event_page_listing_id]))
                ->create([
                    ...$foreignKeyColumns,
                    'id' => $page->id,
                    'title' => $page->title,
                    'type' => $page->type,
                    'payment_option' => $page->payment_option,
                    'event_page_background_image' => $page->event_page_background_image
                ]);

            if (!$epl) {
                Log::channel('dataimport')->debug("id: {$page->id} The event page listing id {$page->event_page_listing_id} did not exists and was created. Promotional_page: ".json_encode($page));
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
        PromotionalPage::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
