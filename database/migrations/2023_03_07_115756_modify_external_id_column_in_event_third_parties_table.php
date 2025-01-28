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
        Schema::table('event_third_parties', function (Blueprint $table) {
            if (Schema::hasColumn('event_third_parties', 'external_id')) {
                $table->string('external_id')->nullable()->change();
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
        Schema::table('event_third_parties', function (Blueprint $table) {
            if (Schema::hasColumn('event_third_parties', 'external_id')) {
                $table->unsignedBigInteger('external_id')->nullable()->change();
            }
        });
    }
};
