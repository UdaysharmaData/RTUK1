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
        Schema::create('event_page_event_category_event_page_listing', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->unsignedBigInteger('event_category_event_page_listing_id');
            $table->foreign('event_category_event_page_listing_id', 'e_p_e_c_e_p_l_event_category_event_page_listing_id')->references('id')->on('event_category_event_page_listing')->onDelete('cascade');
            // $table->foreignId('event_category_event_page_listing_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('event_page_id');
            $table->foreign('event_page_id', 'e_p_e_c_e_p_l_event_page_id')->references('id')->on('event_pages')->onDelete('cascade');
            // $table->foreignId('event_page_id')->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('event_page_event_category_event_page_listing');
    }
};
