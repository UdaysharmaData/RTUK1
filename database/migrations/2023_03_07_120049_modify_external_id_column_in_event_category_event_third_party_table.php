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
        Schema::table('event_category_event_third_party', function (Blueprint $table) {
            if (Schema::hasColumn('event_category_event_third_party', 'external_id')) {
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
        Schema::table('event_category_event_third_party', function (Blueprint $table) {
            if (Schema::hasColumn('event_category_event_third_party', 'external_id')) {
                $table->unsignedBigInteger('external_id')->nullable()->change();
            }
        });
    }
};
