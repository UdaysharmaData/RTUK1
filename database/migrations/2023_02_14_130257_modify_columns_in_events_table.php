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
            if (Schema::hasColumn('events', 'route_info')) {
                $table->renameColumn('route_info', 'route_info_description');
            }

            if (Schema::hasColumn('events', 'what_is_included')) {
                $table->renameColumn('what_is_included', 'what_is_included_description');
            }

            if (! Schema::hasColumn('events', 'route_info_code')) {
                $table->longText('route_info_code')->nullable()->after('route_info');
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
            if (Schema::hasColumn('events', 'route_info_description')) {
                $table->renameColumn('route_info_description', 'route_info');
            }

            if (Schema::hasColumn('events', 'what_is_included_description')) {
                $table->renameColumn('what_is_included_description', 'what_is_included');
            }

            if (Schema::hasColumn('events', 'route_info_code')) {
                $table->dropColumn('route_info_code');
            }
        });
    }
};
