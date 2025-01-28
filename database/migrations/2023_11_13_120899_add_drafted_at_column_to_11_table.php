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
        collect(static::$affectedTables)->each(function ($table) {
            $_table = $table;

            Schema::table($table, function (Blueprint $table) use ($_table) {

                if (!Schema::hasColumn($_table, 'drafted_at')) {
                    $table->timestamp('drafted_at')->nullable()->after('updated_at');
                }

            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        collect(static::$affectedTables)->each(function ($table) {
            $_table = $table;

            Schema::table($table, function (Blueprint $table) use ($_table) {

                if (Schema::hasColumn($_table, 'drafted_at')) {
                    $table->dropColumn('drafted_at');
                }
            });
        });
    }

    protected static $affectedTables = [
        'events',
        'event_categories',
        'regions',
        'cities',
        'venues',
        'medals',
        'series',
        'sponsors',
        'experiences',
        'combinations',
        'pages'
    ];
};
