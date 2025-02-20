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
        Schema::create('national_averages', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('event_category_id')->constrained()->cascadeOnDelete();
            $table->string('gender'); // [male, female]
            $table->year('year');
            $table->time('time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('national_averages');
    }
};
