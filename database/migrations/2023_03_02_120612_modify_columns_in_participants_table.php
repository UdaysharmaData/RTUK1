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
        Schema::table('participants', function (Blueprint $table) {
            if (Schema::hasColumn('participants', 'how_much_raised')) {
                $table->renameColumn('how_much_raised', 'fundraising_target');
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
        Schema::table('participants', function (Blueprint $table) {
            if (Schema::hasColumn('participants', 'fundraising_target')) {
                $table->renameColumn('fundraising_target', 'how_much_raise');
            }
        });
    }
};
