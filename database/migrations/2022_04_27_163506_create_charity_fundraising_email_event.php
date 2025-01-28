<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * The events of the charity_fundraising_email table
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charity_fundraising_email_event', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->unsignedBigInteger('charity_fundraising_email_id');
            $table->foreign('charity_fundraising_email_id', 'c_f_e_e_charity_fundraising_email_id')->references('id')->on('charity_fundraising_email')->onDelete('cascade');
            // $table->foreignId('charity_fundraising_email_id')->constrained('charity_fundraising_emails')->cascadeOnDelete();
            // "Identifier name 'charity_fundraising_email_event_charity_fundraising_email_id_foreign' is too long" error appears when using $table->foreignId() above

            $table->foreignId('event_id')->constrained()->restrictOnDelete();
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
        Schema::dropIfExists('charity_fundraising_email_event');
    }
};
