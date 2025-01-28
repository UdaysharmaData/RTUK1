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
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropUnique(['role_id', 'user_id']);
            $table->foreignId('site_id')->nullable()->after('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->unique(['role_id', 'user_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropUnique(['role_id', 'user_id', 'site_id']);
            $table->dropConstrainedForeignId('site_id');
            $table->unique(['role_id', 'user_id']);
        });
    }
};
