<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('combinations', function (Blueprint $table) {
            // Remove existing foreign key constraints and columns
            $table->dropForeign(['event_category_id']);
            $table->dropForeign(['region_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['venue_id']);

            $table->dropColumn(['event_category_id', 'region_id', 'city_id', 'venue_id', 'series_id', 'date']);

            // Add new columns if they do not exist
            if (!Schema::hasColumn('combinations', 'event_category_id')) {
                $table->json('event_category_id')->nullable()->after('site_id');
            }
            if (!Schema::hasColumn('combinations', 'region_id')) {
                $table->json('region_id')->nullable()->after('event_category_id');
            }
            if (!Schema::hasColumn('combinations', 'city_id')) {
                $table->json('city_id')->nullable()->after('region_id');
            }
            if (!Schema::hasColumn('combinations', 'venue_id')) {
                $table->json('venue_id')->nullable()->after('city_id');
            }
            if (!Schema::hasColumn('combinations', 'series_id')) {
                $table->json('series_id')->nullable()->after('venue_id');
            }
            if (!Schema::hasColumn('combinations', 'date')) {
                $table->timestamp('date')->nullable()->after('series_id');
            }
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
            // Drop newly added columns
            $table->dropColumn(['series_id', 'date']);

            // Re-add removed columns with foreign key constraints
            $table->foreignId('event_category_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('region_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
        });
    }
};
