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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->integer('site_id');
            $table->string('event_name');
            $table->integer('event_id');
            $table->integer('event_category_event_third_party_id');
            $table->integer('event_category_id');
            $table->string('event_category_name');
            $table->integer('total_converted_till_date');
            $table->integer('total_ldt_count');
            $table->integer('total_converted_current');
            $table->integer('total_failed_current');
            $table->string('total_failed_till_date');
            $table->string('ldt_event_name');
            $table->string('ldt_race_id');
            $table->string('ldt_occurrence_id');
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
        Schema::dropIfExists('reports');
    }
};
