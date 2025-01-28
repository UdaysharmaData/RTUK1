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
        Schema::create('event_page_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete(); // replaces epl_charity
            $table->foreignId('corporate_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('ref');
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            // $table->string('featured_event_pages')->nullable(); // moved to event_page_event_page_listing
            $table->boolean('other_events')->default(0); // The other_events are found under the event_category_event_page_listings table.
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('background_image')->nullable();

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
        Schema::dropIfExists('event_page_listings');
    }
};
