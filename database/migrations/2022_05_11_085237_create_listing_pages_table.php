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
        Schema::create('listing_pages', function (Blueprint $table) { // replaces listings_pages
            $table->id();
            // $table->foreignId('charity_id')->nullable()->constrained()->nullOnDelete(); // redundant. Use/Save to the charity_listings charity_id attribute.
            $table->foreignId('charity_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('ref');
            $table->string('title');
            $table->string('type'); // ['wizard', 'creator']
            // $table->string('logo')->nullable(); // redundant. Use/Save to the charity_listings logo attribute.
            // $table->string('banner_image')->nullable(); // redundant. Use/Save to the charity_listings banner_image attribute.
            // $table->string('background_image')->nullable(); // redundant. Use/Save to the charity_listings background_image attribute.
            // $table->boolean('include_2_year_members')->default(0); // redundant. Use/Save to the charity_listings include_2_year_members attribute.
            // $table->text('partner_listing_description')->nullable(); // redundant. Use/Save to the charity_listings description attribute.
            $table->text('event_page_description')->nullable(); // keep this attribute since there are many event pages.
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
        Schema::dropIfExists('listing_pages');
    }
};
