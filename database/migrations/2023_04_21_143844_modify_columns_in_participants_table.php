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
        Schema::table('participants', function (Blueprint $table) {
            if (! Schema::hasColumn('participants', 'speak_with_coach')) {
                $table->boolean('speak_with_coach')->after('enable_family_registration')->nullable();
            }

            if (! Schema::hasColumn('participants', 'hear_from_partner_charity')) {
                $table->boolean('hear_from_partner_charity')->after('speak_with_coach')->nullable();
            }

            if (! Schema::hasColumn('participants', 'reason_for_participating')) {
                $table->text('reason_for_participating')->after('hear_from_partner_charity')->nullable();
            }

            if (Schema::hasColumn('participants', 'estimated_finish_time')) {
                $table->string('estimated_finish_time')->nullable()->change();
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
        Schema::table('participants', function (Blueprint $table) {
            if (Schema::hasColumn('participants', 'speak_with_coach')) {
                $table->dropColumn('speak_with_coach');
            }

            if (Schema::hasColumn('participants', 'reason_for_participating')) {
                $table->dropColumn('reason_for_participating');
            }

            if (Schema::hasColumn('participants', 'hear_from_partner_charity')) {
                $table->dropColumn('hear_from_partner_charity');
            }

            if (Schema::hasColumn('participants', 'estimated_finish_time')) {
                $table->time('estimated_finish_time')->nullable()->change();
            }
        });
    }
};
