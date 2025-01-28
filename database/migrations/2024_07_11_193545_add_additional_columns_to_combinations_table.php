<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalColumnsToCombinationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('combinations', function (Blueprint $table) {
            $table->json('event_category_id')->nullable()->after('site_id');
            $table->json('region_id')->nullable()->after('event_category_id');
            $table->json('city_id')->nullable()->after('region_id');
            $table->json('venue_id')->nullable()->after('city_id');
            $table->json('series_id')->nullable()->after('venue_id');
            $table->timestamp('date')->nullable()->after('series_id');
            $table->string('year')->nullable()->after('description');
            $table->string('month')->nullable()->after('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('combinations', function (Blueprint $table) {
            $table->dropColumn(['event_category_id', 'region_id', 'city_id', 'venue_id', 'series_id', 'date', 'year', 'month']);
        });
    }
}
