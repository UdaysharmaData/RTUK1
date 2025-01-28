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
        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'state')) {
                $table->dropColumn('state');
            }

            if (Schema::hasColumn('profiles', 'region')) {
                $table->renameColumn('region', 'state');
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
        Schema::table('profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profiles', 'region') && Schema::hasColumn('profiles', 'state')) {
                $table->renameColumn('state', 'region');
            }
        });

        Schema::table('profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profiles', 'state') && Schema::hasColumn('profiles', 'region')) {
                $table->string('state')->after('region')->nullable();
            }
        });
    }
};
