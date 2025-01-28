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
        Schema::table('meta', function (Blueprint $table) {
            if (! Schema::hasColumn('meta', 'canonical_url')) {
                $table->string('canonical_url')->nullable()->after('keywords');
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
        Schema::table('meta', function (Blueprint $table) {
            if (Schema::hasColumn('meta', 'canonical_url')) {
                $table->dropColumn('canonical_url');
            }
        });
    }
};
