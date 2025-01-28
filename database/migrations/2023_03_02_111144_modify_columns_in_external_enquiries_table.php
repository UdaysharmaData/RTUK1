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
            if (Schema::hasColumn('external_enquiries', 'name')) {
                $table->string('name')->nullable()->change();
            }

            if (! Schema::hasColumn('external_enquiries', 'last_name')) {
                $table->string('last_name')->nullable()->after('name');
            }

            if (Schema::hasColumn('external_enquiries', 'name')) {
                $table->renameColumn('name', 'first_name');
            }

            if (Schema::hasColumn('external_enquiries', 'email')) {
                $table->string('email')->nullable()->change();
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
        Schema::table('external_enquiries', function (Blueprint $table) {
            if (Schema::hasColumn('external_enquiries', 'first_name')) {
                $table->renameColumn('first_name', 'name');
            }

            if (Schema::hasColumn('external_enquiries', 'last_name')) {
                $table->dropColumn('last_name');
            }

            if (Schema::hasColumn('external_enquiries', 'email')) {
                $table->string('email')->nullable(false)->change();
            }
        });
    }
};
