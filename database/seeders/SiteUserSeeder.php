<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\User\Models\SiteUser;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SiteUserSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->truncateTable();

        Log::channel('dataimport')->debug('The site user seeder logs');

        $this->truncateTable();

        SiteUser::factory()->count(10)->create();
    }

    /**
     * Truncates the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        SiteUser::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
