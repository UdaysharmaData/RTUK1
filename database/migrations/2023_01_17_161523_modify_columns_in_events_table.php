<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Change the default values of these mandatory columns (by default) from 0 to 1.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'reg_dob')) {
                $table->boolean('reg_dob')->default(1)->change();
            }

            if (Schema::hasColumn('events', 'reg_phone')) {
                $table->boolean('reg_phone')->default(1)->change();
            }

            if (Schema::hasColumn('events', 'reg_gender')) {
                $table->boolean('reg_gender')->default(1)->change();
            }

            if (Schema::hasColumn('events', 'reminder')) {
                $table->string('reminder')->nullable()->default(null)->change();
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
            if (Schema::hasColumn('events', 'reg_dob')) {
                $table->boolean('reg_dob')->default(0)->change();
            }

            if (Schema::hasColumn('events', 'reg_phone')) {
                $table->boolean('reg_phone')->default(0)->change();
            }

            if (Schema::hasColumn('events', 'reg_gender')) {
                $table->boolean('reg_gender')->default(0)->change();
            }

            if (Schema::hasColumn('events', 'reminder')) {
                $table->string('reminder')->default('none')->change();
            }
        });
    }
};
