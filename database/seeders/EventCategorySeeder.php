<?php

namespace Database\Seeders;

use DB;
use Str;
use File;
use Schema;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Database\Traits\SlugTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use App\Enums\PredefinedApiClientEnum;
use Illuminate\Support\Facades\Storage;
use App\Modules\Event\Models\EventCategory;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventCategorySeeder extends Seeder
{
    use WithoutModelEvents, EmptySpaceToDefaultData, SlugTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event category seeder logs');

        $this->truncateTable();

        $categories = DB::connection('mysql_2')->table('event_categories')->get();

        foreach ($categories as $category) {
            $site = $category->type == 'rankings' ? Site::where('domain', PredefinedApiClientEnum::RunThroughHub->value)->first() : Site::find($category->site_id);

            $_category = EventCategory::factory()
                ->for($site ?? Site::factory()->create(['id' => $category->site_id]))
                ->create([
                    'id' => $category->id,
                    'ref' => Str::orderedUuid(),
                    'name' => $category->name,
                    'slug' => $this->getUniqueSlug(EventCategory::class, $this->valueOrDefault($category->url, Str::slug($category->name))),
                    'color' => $category->color,
                    'distance_in_km' => $category->distance_in_km
                ]);

            // Save the image
            if ($this->valueOrDefault($category->image) && Storage::disk('sfc')->exists($category->image)) { // Copy the image from the sport-for-api disk if it exists

                $upload = $_category->upload()->updateOrCreate([], [
                    'ref' => Str::orderedUuid(),
                    'title' => $_category->name,
                    'url' => config('app.images_path') . str_replace('uploads/', '', $category->image),
                    'use_as' => UploadUseAsEnum::Image,
                    'type' => UploadTypeEnum::Image
                ]);

                Storage::disk('public')->put($upload->real_path, Storage::disk('sfc')->get($category->image)); // Copy the image
            }

            if (!$site) {
                Log::channel('dataimport')->debug("id: {$category->id} The site id  {$category->site_id} did not exists and was created. Event_category: ".json_encode($category));
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
        EventCategory::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
