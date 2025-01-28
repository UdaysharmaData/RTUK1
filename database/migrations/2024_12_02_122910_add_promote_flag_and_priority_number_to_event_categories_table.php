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
        Schema::table('event_categories', function (Blueprint $table) {
            $table->boolean('promote_flag')->default(0)->after('distance_in_km'); // 0 or 1, default is 0
            $table->unsignedSmallInteger('priority_number')->default(0)->after('promote_flag'); // Range 0 to 200
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_categories', function (Blueprint $table) {
            $table->dropColumn(['promote_flag', 'priority_number']);
        });
    }
};
