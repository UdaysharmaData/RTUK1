<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Some events require specific charities to run them (refer to the only_included_charities attribute on the events table).
     * This table stores those events alongside the charities allowed to run them with the included value as type.
     *
     * Also, some events does not allow some charities to run them (partner_disabled attribute on the charity_events table on the previous database).
     * This table stores those events alongside the charities not allowed to run them with the excluded value as type.
     * 
     * @return void
     */
    public function up()
    {
        Schema::create('charity_event', function (Blueprint $table) { // Replaces charity_places.
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('type'); // ['included', 'excluded']
            $table->timestamps();
     
            $table->unique(['event_id', 'charity_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('charity_event');
    }
};
