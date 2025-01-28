<?php

namespace Database\Seeders;

use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\CharityMembership;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CharityMembershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity membership seeder logs');

        $this->truncateTable();

        CharityMembership::factory()->count(100)->create();
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        CharityMembership::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
