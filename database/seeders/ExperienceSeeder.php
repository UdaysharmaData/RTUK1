<?php

namespace Database\Seeders;

use Schema;
use App\Models\Experience;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ExperienceSeeder extends Seeder
{
    use EmptySpaceToDefaultData, WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The experiences seeder logs');

        $this->truncateTable();

        Experience::factory()->count(10)->create();
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        Experience::truncate();
        Schema::enableForeignKeyConstraints();
    }

}
