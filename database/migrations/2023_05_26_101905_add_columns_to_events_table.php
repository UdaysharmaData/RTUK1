<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            if (!Schema::hasColumn('events', 'serie_id')) {
                $table->foreignId('serie_id')->after('venue_id')->nullable()
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('events', 'sponsor_id')) {
                $table->foreignId('sponsor_id')->after('serie_id')->nullable()
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
            if (Schema::hasColumn('events', 'serie_id')) {
                $table->dropConstrainedForeignId('serie_id');
            }

            if (Schema::hasColumn('events', 'sponsor_id')) {
                $table->dropConstrainedForeignId('sponsor_id');
            }
        });
    }
};
