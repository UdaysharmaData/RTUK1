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
            if (Schema::hasColumn('participant_extras', 'speak_with_coach')) {
                $table->dropColumn('speak_with_coach');
            }

            if (Schema::hasColumn('participant_extras', 'hear_from_partner_charity')) {
                $table->dropColumn('hear_from_partner_charity');
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
            if (! Schema::hasColumn('participant_extras', 'speak_with_coach')) {
                $table->boolean('speak_with_coach')->after('weekly_physical_activity')->nullable();
            }

            if (! Schema::hasColumn('participant_extras', 'hear_from_partner_charity')) {
                $table->boolean('hear_from_partner_charity')->after('speak_with_coach')->nullable();
            }
        });
    }
};
