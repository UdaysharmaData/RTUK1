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
            if (!Schema::hasColumn('uploads', 'resized')) {
                $table->boolean('resized')->default(false)->after('description')->comment('Indicates whether the image has been resized or not. Egg: mobile, tablet, desktop, etc.');
            }

            if (!Schema::hasColumn('uploads', 'private')) {
                $table->boolean('private')->default(false)->after('description');
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
            if (Schema::hasColumn('uploads', 'resized')) {
                $table->dropColumn('resized');
            }

            if (Schema::hasColumn('uploads', 'private')) {
                $table->dropColumn('private');
            }
        });
    }
};
