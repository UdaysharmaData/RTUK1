<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * The model contains preset primary and secondary partners (charities) used when creating a charity listing through the listing pages.
     * @return void
     */
    public function up()
    {
        Schema::create('listing_page_charities', function (Blueprint $table) { // replaces listings_pages_charities
            $table->id();
            $table->uuid('ref');
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete(); // replaces charities. Previous saved the charities as an array.
            $table->string('type'); // ['primary_partner', 'secondary_partner'] replaces ['partner', 'secondary_partner']
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
        Schema::dropIfExists('listing_page_charities');
    }
};
