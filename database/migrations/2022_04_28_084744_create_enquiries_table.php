<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Guests (visitors) enquiring to participate in an event through a charity or corporate.
     * TODO: Make the description more explicite (cases where data gets saved on this table) after going through 
     * the registration pages that save data on this table
     *
     * @return void
     */
    public function up()
    {
        Schema::create('enquiries', function (Blueprint $table) { // renamed signups table to enquiries.
            $table->id();
            $table->uuid('ref');
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('charity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('corporate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // replaces fullname
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('sex')->nullable();
            $table->string('postcode')->nullable();
            $table->string('status'); // ['assigned', 'not-assigned']
            $table->boolean('made_contact')->default(0);
            $table->boolean('converted')->default(0);
            $table->mediumText('comments')->nullable();
            $table->string('custom_charity')->nullable();
            $table->string('how_much_raise')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('enquiries');
    }
};
