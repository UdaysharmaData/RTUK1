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
        Schema::table('ongoing_external_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('ongoing_external_transactions', 'state')) {
                $table->string('state')->nullable()->after('status');
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
        Schema::table('ongoing_external_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('ongoing_external_transactions', 'state')) {
                $table->dropColumn('state');
            }
        });
    }
};
