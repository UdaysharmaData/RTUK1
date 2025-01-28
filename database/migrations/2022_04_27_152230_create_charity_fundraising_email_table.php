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
        Schema::create('charity_fundraising_email', function (Blueprint $table) { // replaces charity_drips
            $table->id();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fundraising_email_id')->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->boolean('status')->default(1);
            $table->text('content')->nullable();
            // $table->string('events'); // moved to charity_fundraising_email_event table
            $table->string('from_name');
            $table->string('from_email');
            $table->boolean('all_events')->default(0); // Added column. If set to true, runs the fundraising email for all the events. This helps to reduce the size of the charity_fundraising_email_events table. Creating records for all the events and associating it to the fundraising email is space consuming.
                                          // On the previous database, the events column takes [0] for all events. This column somehow maintains that implementation.
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
        Schema::dropIfExists('charity_fundraising_email');
    }
};
