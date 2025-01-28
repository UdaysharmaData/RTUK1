<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Database\Traits\ForeignKeyExistsTrait;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use ForeignKeyExistsTrait;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medals', function (Blueprint $table) {
            if (Schema::hasColumn('medals', 'site_id')) {
                if (! $this->foreignKeyExists('medals', 'site_id')) {
                    $table->foreign('site_id')->references('id')->on('sites')->onUpdate('cascade')->onDelete('cascade');
                }
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
        Schema::table('medals', function (Blueprint $table) {
            //
        });
    }
};
