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
            $table->tinyInteger('processed')->default(0)->after('token')->comment('1 = processed, 0 = not processed')->nullable();

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
            //
        });
    }
};
