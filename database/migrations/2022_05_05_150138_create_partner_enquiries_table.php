<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enquires to join our partners
     * @return void
     */
    public function up()
    {
        Schema::create('partner_enquiries', function (Blueprint $table) { // Replaces partner_signups
            $table->id();
            $table->uuid('ref');
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // replaces company_name
            $table->string('website');
            $table->mediumText('information');
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
        Schema::dropIfExists('partner_enquiries');
    }
};
