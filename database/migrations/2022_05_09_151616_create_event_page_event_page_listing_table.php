<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores featured event pages of the event page listing.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_page_event_page_listing', function (Blueprint $table) { // replaces event_page_event_page_listings table.
            $table->id();
            $table->uuid('ref');
            $table->unsignedBigInteger('event_page_listing_id');
            $table->foreign('event_page_listing_id', 'e_p_e_p_l_event_page_listing_id')->references('id')->on('event_page_listings')->onDelete('cascade');
            // $table->foreignId('event_page_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_page_id')->constrained()->cascadeOnDelete();
            // $table->string('image')->nullable(); // implement CanHaveUploadableResource
            $table->string('video')->nullable(); // stores url
            $table->timestamps();

            /**
             * OTHER CHANGES TO CONSIDER WHEN IMPORTING DATA
             * The event_page_event_page_listings table (on the previous database) was used to save featured event pages custom images and videos.
             * It has been renamed to event_page_event_page_listing and it is now used to save both the featured event pages of the event page listing (former featured_event_pages attribute on event_page_listings table) and the custom images & videos (on the former event_page_event_page_listings table).
             */
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_page_event_page_listing');
    }
};
