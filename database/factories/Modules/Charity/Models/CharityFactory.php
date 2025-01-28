<?php

// namespace Database\Factories;
namespace Database\Factories\Modules\Charity\Models;

use DB;
use Str;
use File;
use Database\Traits\SiteTrait;
use App\Enums\UploadUseAsEnum;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\CharityCategory;
// use Illuminate\Database\Eloquent\Factories\Factory;

/**
// * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Charity\Models\Charity>
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\Charity>
 */
class CharityFactory extends CustomFactory
{
    use SiteTrait;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        // $charities = DB::connection('mysql_2')->table('charities')->get()->pluck('title');
        // $name = $this->faker->randomElement($charities);
        $name = $this->faker->unique()->name();
        $slug = Str::slug($name);

        return [
            'ref' => Str::random(10),
            'charity_category_id' => CharityCategory::factory(),
            'name' => $name,
            'slug' => $slug,
            'external_strapline' => $this->faker->sentence()
        ];
    }
}