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
        Schema::create('total_places_notifications', function (Blueprint $table) { // replaces ticker_notifications
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            // $table->boolean('status')->default(1); // removed. not necessary
            // TODO: Some columns are lacking. Take a look at the comment in the TickerNotification model
            $table->timestamps();
        
            $table->unique(['event_id', 'charity_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('total_places_notifications');
    }
};
