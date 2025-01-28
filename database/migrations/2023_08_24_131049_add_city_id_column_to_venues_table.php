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
        Schema::table('venues', function (Blueprint $table) {
            if (! Schema::hasColumn('venues', 'city_id')) {
                $table->foreignId('city_id')->nullable()->after('slug')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
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
        Schema::table('venues', function (Blueprint $table) {
            if (Schema::hasColumn('venues', 'city_id')) {
                $table->dropConstrainedForeignId('city_id');
            }
        });
    }
};
