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
        Schema::create('promotional_pages', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('charity_id')->constrained()->restrictOnDelete(); // redundant. Present in the event_page_listings
            $table->foreignId('event_page_listing_id')->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('title');
            $table->string('type')->default('promotional_page_1'); // ['promotional_page_1', 'promotional_page_2']
            // $table->text('description')->nullable(); // redundant. Use/Save to the event_page_listings description attribute.
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete(); // replaces county
            $table->string('payment_option')->default('participant'); // ['participant', 'charity']
            $table->string('event_page_background_image')->nullable();
            // $table->string('event_page_listing_background_image')->nullable(); // redundant. Use/Save to the event_page_listings background_image attribute.
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
        Schema::dropIfExists('promotional_pages');
    }
};
