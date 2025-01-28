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
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'city')) {
                $table->dropColumn('city');
            }

            if (Schema::hasColumn('events', 'venue')) {
                $table->dropColumn('venue');
            }
 
            if (! Schema::hasColumn('events', 'city_id')) {
                $table->foreignId('city_id')->after('region_id')->nullable()
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
  
            if (! Schema::hasColumn('events', 'venue_id')) {
                $table->foreignId('venue_id')->after('city_id')->nullable()
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
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
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'city')) {
                $table->string('city')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('events', 'venue')) {
                $table->string('venue')->nullable()->after('city');
            }

            if (Schema::hasColumn('events', 'city_id')) {
                $table->dropConstrainedForeignId('city_id');
            }

            if (Schema::hasColumn('events', 'venue_id')) {
                $table->dropConstrainedForeignId('venue_id');
            }
        });
    }
};
