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
        Schema::table('uploads', function (Blueprint $table) {
            if (! Schema::hasColumn('uploads', 'caption')) {
                $table->string('caption')->nullable()->after('metadata');
            }

            if (! Schema::hasColumn('uploads', 'alt')) {
                $table->string('alt')->nullable()->after('caption');
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
        Schema::table('uploads', function (Blueprint $table) {
            if (Schema::hasColumn('uploads', 'caption')) {
                $table->dropColumn('caption');
            }

            if (Schema::hasColumn('uploads', 'alt')) {
                $table->dropColumn('alt');
            }
        });
    }
};
