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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->change();
            }

            if (Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->change();
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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable(false)->change();
            }

            if (Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable(false)->change();
            }
        });
    }
};
