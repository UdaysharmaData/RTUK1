<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enquiries made by event owners for their event to be showcased and published in our calendar.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_enquiries', function (Blueprint $table) { // replaces event_signups
            $table->id();
            $table->uuid('ref');
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('distance');
            $table->string('entrants');
            $table->string('website');
            $table->string('address_1');
            $table->string('address_2');
            $table->string('city');
            $table->string('postcode');
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone');
            // $table->boolean('terms_conditions'); // not needed
            // $table->boolean('privacy_policy'); // not needed
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
        Schema::dropIfExists('event_enquiries');
    }
};
