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
            if (! Schema::hasColumn('events', 'reg_first_name')) {
                $table->boolean('reg_first_name')->default(1)->after('reg_age_on_race_day');
            }

            if (! Schema::hasColumn('events', 'reg_last_name')) {
                $table->boolean('reg_last_name')->default(1)->after('reg_first_name');
            }

            if (! Schema::hasColumn('events', 'reg_email')) {
                $table->boolean('reg_email')->default(1)->after('reg_last_name');
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
            if (Schema::hasColumn('events', 'reg_first_name')) {
                $table->dropColumn('reg_first_name');
            }

            if (Schema::hasColumn('events', 'reg_last_name')) {
                $table->dropColumn('reg_last_name');
            }

            if (Schema::hasColumn('events', 'reg_email')) {
                $table->dropColumn('reg_email');
            }
        });
    }
};
