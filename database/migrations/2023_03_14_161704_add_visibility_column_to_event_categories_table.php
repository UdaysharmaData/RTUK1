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
            if (! Schema::hasColumn('event_categories', 'visibility')) {
                $table->string('visibility')->default('public')->after('ref'); // [public, private]
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
        Schema::table('event_categories', function (Blueprint $table) {
            if (Schema::hasColumn('event_categories', 'visibility')) {
                $table->dropColumn('visibility');
            }
        });
    }
};
