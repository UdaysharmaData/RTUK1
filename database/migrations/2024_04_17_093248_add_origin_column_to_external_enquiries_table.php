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
        Schema::table('external_enquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('external_enquiries', 'origin')) {
                $table->string('origin')->nullable()->after('participant_id');
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
        Schema::table('external_enquiries', function (Blueprint $table) {
            if (Schema::hasColumn('external_enquiries', 'origin')) {
                $table->dropColumn('origin');
            }
        });
    }
};
