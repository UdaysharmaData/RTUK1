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
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'reg_ethnicity')) {
                $table->boolean('reg_ethnicity')->after('reg_passport_number')->default(0);
            }

            if (! Schema::hasColumn('events', 'reg_weekly_physical_activity')) {
                $table->boolean('reg_weekly_physical_activity')->after('reg_ethnicity')->default(0);
            }

            if (! Schema::hasColumn('events', 'reg_speak_with_coach')) {
                $table->boolean('reg_speak_with_coach')->after('reg_weekly_physical_activity')->default(0);
            }

            if (! Schema::hasColumn('events', 'reg_hear_from_partner_charity')) {
                $table->boolean('reg_hear_from_partner_charity')->after('reg_speak_with_coach')->default(0);
            }

            if (! Schema::hasColumn('events', 'reg_reason_for_participating')) {
                $table->boolean('reg_reason_for_participating')->after('reg_hear_from_partner_charity')->default(0);
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
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'reg_ethnicity')) {
                $table->dropColumn('reg_ethnicity');
            }

            if (Schema::hasColumn('events', 'reg_weekly_physical_activity')) {
                $table->dropColumn('reg_weekly_physical_activity');
            }

            if (Schema::hasColumn('events', 'reg_speak_with_coach')) {
                $table->dropColumn('reg_speak_with_coach');
            }

            if (Schema::hasColumn('events', 'reg_reason_for_participating')) {
                $table->dropColumn('reg_reason_for_participating');
            }

            if (Schema::hasColumn('events', 'reg_hear_from_partner_charity')) {
                $table->dropColumn('reg_hear_from_partner_charity');
            }
        });
    }
};
