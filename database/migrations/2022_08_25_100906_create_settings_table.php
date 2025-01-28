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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->unique()->cascadeOnDelete();
            $table->uuid('ref');

            // WILL BE REVISED BASED ON THE NEW PORTAL REQUIREMENTS

            // $table->string('slider_title')->nullable();
            // $table->string('slider_text')->nullable();
            // $table->string('left_search_title')->nullable();
            // $table->string('left_search_text')->nullable();
            // $table->string('right_search_title')->nullable();
            // $table->string('right_search_text')->nullable();
            // $table->string('facebook')->nullable(); // implement CanHaveManySociableResource
            // $table->string('twitter')->nullable();  // implement CanHaveManySociableResource
            // $table->string('instagram')->nullable(); // implement CanHaveManySociableResource
            // $table->string('google')->nullable();    // implement CanHaveManySociableResource
            // $table->string('pinterest')->nullable(); // implement CanHaveManySociableResource

            // $table->string('events_grid')->nullable();
            // $table->string('event_ids')->nullable(); // 
            // $table->string('event_title')->nullable();
            // $table->string('event_text')->nullable();
            // $table->string('video')->nullable();
            // $table->string('news_title')->nullable();
            // $table->string('news_text')->nullable();
            // $table->string('ideas_title')->nullable();
            // $table->string('ideas_text')->nullable();
            // $table->string('sidebar_title')->nullable();
            // $table->string('sidebar_text')->nullable();
            // $table->string('sidebar_text')->nullable();

            // Moved to the setting_custom_fields table

            // $table->integer('classic_renewal');
            // $table->integer('new_classic_renewal');
            // $table->integer('premium_renewal');
            // $table->integer('new_premium_renewal');
            // $table->integer('two_year_renewal');
            // $table->integer('new_two_year_renewal');
            // $table->integer('partner_renewal');
            // $table->integer('premium_membership_default_places');
            // $table->integer('premium_membership_default_places');

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
        Schema::dropIfExists('settings');
    }
};
