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
        Schema::create('analytics_total_counts', function (Blueprint $table) {
            $table->id();
            $table->morphs('countable');
            $table->unsignedBigInteger('total')->default(0);
            $table->timestamps();

            $table->unique(['countable_type', 'countable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('analytics_total_counts');
    }
};
