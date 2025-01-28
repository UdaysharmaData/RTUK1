<?php

namespace Database\Seeders;

use DB;
use Str;
use Schema;
use Storage;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventPage;
use App\Modules\Corporate\Models\Corporate;
use App\Modules\Event\Models\EventEventPage;

use Database\Traits\FormatDate;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventPageSeeder extends Seeder
{
    use FormatDate, EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event page seeder logs');

        $this->truncateTables();

        $pages = DB::connection('mysql_2')->table('event_pages')->get();
        foreach ($pages as $page) {
            $foreignKeyColumns = [];

            $_eventPage = EventPage::factory();

            // if ($page->event_id) { // check if the event exists
            //     $event = Event::find($page->event_id);
            //     $_eventPage = $_eventPage->for($event ?? Event::factory()->create(['id' => $page->event_id]));

            //     if (!$event) {
            //         Log::channel('dataimport')->debug("id: {$page->id} The event id  {$page->event_id} did not exists and was created. Event_page: ".json_encode($page));
            //     }
            // } else {
            //     $foreignKeyColumns = ['event_id' => null];
            // }

            if ($page->charity_id) { // check if the charity exists
                $charity = Charity::find($page->charity_id);
                $_eventPage = $_eventPage->for($charity ?? Charity::factory()->create(['id' => $page->charity_id]));

                if (!$charity) {
                    Log::channel('dataimport')->debug("id: {$page->id} The charity id  {$page->charity_id} did not exists and was created. Event_page: ".json_encode($page));
                }
            } else {
                $foreignKeyColumns = ['charity_id' => null, ...$foreignKeyColumns];
            }

            if ($page->corporate_id) { // check if the corporate exists
                $corporate = Corporate::find($page->corporate_id);
                $_eventPage = $_eventPage->for($corporate ?? Corporate::factory()->create(['id' => $page->corporate_id]));

                if (!$corporate) {
                    Log::channel('dataimport')->debug("id: {$page->id} The corporate id  {$page->corporate_id} did not exists and was created. Event_page: ".json_encode($page));
                }
            } else {
                $foreignKeyColumns = ['corporate_id' => null, ...$foreignKeyColumns];
            }

            $slug = $this->valueOrDefault($page->slug);

            $_eventPage = $_eventPage->create([
                ...$foreignKeyColumns,
                'id' => $page->id,
                'slug' => $slug ? (EventPage::where('slug', $slug)->first() ? $slug.Str::random(10) : $slug) : null,
                'hide_helper' => $page->hide_helper,
                'fundraising_title' => $page->helper_title,
                'fundraising_description' => $page->helper_text,
                'fundraising_target' => $page->fundraising_target,
                'published' => $page->published,
                'code' => $page->code,
                'all_events' => $page->all_events,
                'fundraising_type' => $this->valueOrDefault($page->fundraising_title),
                'black_text' => $page->black_text,
                'hide_event_description' => $page->hide_event_description,
                'reg_form_only' => $page->reg_form_only,
                'video' => $this->valueOrDefault($page->video),
                'use_enquiry_form' => $page->use_enquiry_form,
                'payment_option' => $page->payment_option,
                'registration_price' => $page->registration_price,
                'registration_deadline' => $this->dateOrNull($page->registration_deadline),
                'created_at' => $page->created_at,
                'updated_at' => $page->updated_at
            ]);

            // Link the event page to it's event(s)
            if ($page->event_ids) {
                foreach(json_decode($page->event_ids) as $event_id) {
                    $event = Event::find($event_id);
                    EventEventPage::factory()
                        ->for($_eventPage)
                        ->for($event ?? Event::factory()->create(['id' => $event_id]))
                        ->create();

                    if (!$event) {
                        Log::channel('dataimport')->debug("id: {$page->id} The event id  {$event_id} did not exists and was created. Event_page: ".json_encode($page));
                    }
                }
            } else if ($page->event_id) {
                $event = Event::find($page->event_id);
                EventEventPage::factory()
                    ->for($_eventPage)
                    ->for($event ?? Event::factory()->create(['id' => $page->event_id]))
                    ->create();

                if (!$event) {
                    Log::channel('dataimport')->debug("id: {$page->id} The event id  {$page->event_id} did not exists and was created. Event_page: ".json_encode($page));
                }
            }

            if ($this->valueOrDefault($page->image)) { // save the image path
                $_eventPage->load('events');

                $image = $_eventPage->upload()->updateOrCreate([], [
                    'title' => $_eventPage->events[0]?->name,
                    'type' => UploadTypeEnum::Image,
                    'use_as' => UploadUseAsEnum::Image,
                    'url' => config('app.images_path') . str_replace('uploads/', '', $page->image)
                ]);

                if (Storage::disk('sfc')->exists($page->image)) { // Copy the image
                    Storage::disk('local')->put('public'.$image->url, Storage::disk('sfc')->get($page->image));
                }
            }
        }
    }

    /**
     * Truncate the tables
     *
     * @return void
     */
    public function truncateTables()
    {
        Schema::disableForeignKeyConstraints();
        EventPage::truncate();
        EventEventPage::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
