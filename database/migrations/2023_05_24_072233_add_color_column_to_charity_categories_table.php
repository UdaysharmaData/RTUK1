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
        Schema::table('charity_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('charity_categories', 'color')) {
                $table->string('color')->nullable()->after('slug');
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
        Schema::table('charity_categories', function (Blueprint $table) {
            if (Schema::hasColumn('charity_categories', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
