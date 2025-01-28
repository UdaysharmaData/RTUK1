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
        Schema::table('cities', function (Blueprint $table) {
            if (! Schema::hasColumn('cities', 'region_id')) {
                $table->foreignId('region_id')->nullable()->after('slug')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
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
        Schema::table('cities', function (Blueprint $table) {
            if (Schema::hasColumn('cities', 'region_id')) {
                $table->dropConstrainedForeignId('region_id');
            }
        });
    }
};
