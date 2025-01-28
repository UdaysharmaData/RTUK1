<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Charities requesting to join our platform (partner with us)
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charity_enquiries', function (Blueprint $table) { // replaces charity_signups
            $table->id();
            $table->uuid('ref');
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('charity_category_id')->nullable()->constrained()->nullOnDelete(); // replaces sector
            $table->string('name');
            $table->integer('registration_number'); // replaces number
            $table->string('website');
            $table->string('address_1');
            $table->string('address_2');
            $table->string('city');
            $table->string('postcode');
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone');
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
        Schema::dropIfExists('charity_enquiries');
    }
};
