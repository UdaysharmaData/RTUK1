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
            if (! Schema::hasColumn('event_third_parties', 'occurrence_id')) {
                $table->string('occurrence_id')->nullable()->after('external_id');
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
            if (Schema::hasColumn('event_third_parties', 'occurrence_id')) {
                $table->dropColumn('occurrence_id');
            }
        });
    }
};
