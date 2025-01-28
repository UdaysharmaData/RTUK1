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
        Schema::table('participant_extras', function (Blueprint $table) {
            if (! Schema::hasColumn('participant_extras', 'distance_like_to_run_here')) {
                $table->string('distance_like_to_run_here')->after('club')->nullable();
            }

            if (!Schema::hasColumn('participant_extras', 'race_pack_posted')) {
                $table->string('race_pack_posted')->after('distance_like_to_run_here')->nullable()->default(null);
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
        Schema::table('participant_extras', function (Blueprint $table) {
            if (Schema::hasColumn('participant_extras', 'race_pack_posted')) {
                $table->dropColumn('race_pack_posted');
            }

            if (Schema::hasColumn('participant_extras', 'distance_like_to_run_here')) {
                $table->dropColumn('distance_like_to_run_here');
            }
        });
    }
};
