<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enquiries sent from our partners.
     * 
     * TODO: Given that all the index names of the data received by the APIExternalEnquiryController@create method 
     * is not known at the time (the data is passed to the create method [ExternalEnquiry::create($data)] instead of beign assigned to the properties of an instance of the class [new ExternalEnquiry()]),
     * most of the attributes below are nullable. During implementation, some attributes may be made required.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('external_enquiries', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('charity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partner_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_category_event_third_party_id')->nullable()->constrained('event_category_event_third_party')->nullOnDelete();
            $table->string('channel_record_id')->nullable(); // The unique identifier of the external record
            $table->foreignId('participant_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('contacted')->default(0);
            $table->boolean('converted')->default(0);
            $table->string('name')->nullable(); // replaces fullname
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('postcode')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->string('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->mediumText('comments')->nullable();
            $table->text('timeline')->nullable();
            $table->string('token')->nullable();
            $table->timestampsTz();
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
        Schema::dropIfExists('external_enquiries');
    }
};
