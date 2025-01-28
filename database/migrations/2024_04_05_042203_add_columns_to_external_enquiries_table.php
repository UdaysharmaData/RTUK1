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
            if (! Schema::hasColumn('external_enquiries', 'ldt_created_at')) {
                $table->dateTime('ldt_created_at')->nullable()->after('token');
            }

            if (! Schema::hasColumn('external_enquiries', 'ldt_updated_at')) {
                $table->dateTime('ldt_updated_at')->nullable()->after('ldt_created_at');
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
            if (Schema::hasColumn('external_enquiries', 'ldt_created_at')) {
                $table->dropColumn('ldt_created_at');
            }

            if (Schema::hasColumn('external_enquiries', 'ldt_updated_at')) {
                $table->dropColumn('ldt_updated_at');
            }
        });
    }
};
