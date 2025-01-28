<?php

namespace Database\Seeders;

Use DB;
Use Str;
use Schema;
use Database\Traits\SlugTrait;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Charity\Models\CharityCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CharityCategorySeeder extends Seeder
{
    use EmptySpaceToDefaultData, SlugTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity category seeder logs');

        $this->truncateTable();

        $categories = DB::connection('mysql_2')->table('categories')->get()->toArray();

        foreach ($categories as $category) {
            $charityCategory = new CharityCategory();
            $charityCategory->id = $category->id;
            $charityCategory->status = $category->status;
            $charityCategory->name = $category->name;
            $charityCategory->slug = $this->getUniqueSlug(CharityCategory::class, $this->valueOrDefault($category->url, Str::slug($category->name)));
            $charityCategory->save();

            if ($this->valueOrDefault($category->image) && Storage::disk('sfc')->exists($category->image)) { // save the image
                $image = $charityCategory->upload()->updateOrCreate([], [
                    'title' => $charityCategory->name,
                    'type' => UploadTypeEnum::Image,
                    'use_as' => UploadUseAsEnum::Image,
                    'url' => config('app.images_path') . str_replace('uploads/', '', $category->image),
                ]);

                Storage::disk('local')->put('public'.$image->url, Storage::disk('sfc')->get($category->image)); // Copy the image
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
        CharityCategory::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
