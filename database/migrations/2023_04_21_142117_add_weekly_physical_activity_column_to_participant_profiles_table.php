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
        Schema::table('participant_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('participant_profiles', 'weekly_physical_activity')) {
                $table->string('weekly_physical_activity')->after('tshirt_size')->nullable();
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
        Schema::table('participant_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('participant_profiles', 'weekly_physical_activity')) {
                $table->dropColumn('weekly_physical_activity');
            }
        });
    }
};
