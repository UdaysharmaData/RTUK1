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
        Schema::create('event_category_event_page_listing', function (Blueprint $table) { // replaces event_category_event_page_listings
            $table->id();
            $table->uuid('ref');
            $table->unsignedBigInteger('event_page_listing_id');
            $table->foreign('event_page_listing_id', 'e_c_e_p_l_event_page_listing_id')->references('id')->on('event_page_listings')->onDelete('cascade');
            // $table->foreignId('event_page_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_category_id')->constrained()->cascadeOnDelete();
            // $table->string('event_pages'); // moved to event_page_event_category_event_page_listing
            $table->smallInteger('priority')->nullable();
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
        Schema::dropIfExists('event_category_event_page_listing');
    }
};
