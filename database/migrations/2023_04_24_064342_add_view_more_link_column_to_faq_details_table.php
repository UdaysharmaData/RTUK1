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
        Schema::table('faq_details', function (Blueprint $table) {
            if (! Schema::hasColumn('faq_details', 'view_more_link')) {
                $table->string('view_more_link')->nullable()->after('answer');
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
        Schema::table('faq_details', function (Blueprint $table) {
            if (Schema::hasColumn('faq_details', 'view_more_link')) {
                $table->dropColumn('view_more_link');
            }
        });
    }
};
