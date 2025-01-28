<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This table and the charity_listing_charity table stores charity information which are displayed on a page.
     * The charity description, video, logo are displayed on the page alongside other charities (primary, secondary and 2_year membership)
     * which are partners to the charity.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charity_listings', function (Blueprint $table) { // replaces the partner_listings table
            $table->id();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete(); // replaces main_charity
            $table->uuid('ref');
            $table->string('title');
            $table->string('slug');
            $table->longText('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('background_image')->nullable();
            $table->string('url')->nullable(); // replaces main_charity_custom_url. It is a custom url.
            $table->string('video')->nullable(); // replaces main_charity_video
            $table->boolean('show_2_year_members')->default(1);
            // The charity_partner_listings table on the previous system (from the code) is only used to save custom urls (optional) for
            // the partner (partner_charities, secondary_partner_charities) and 2_year charities of the main charity (charity_id above).
            // The charity_charity_listing table will now be used to store the data of the 2 columns below.
            // Do well to take this into consideration when working on this section.

            // Also for charity listings having 2_year charities (show_2_year_members == 1), the charity_charity_listing table will only save 2_year charities for which the url has been customized.

//           $table->string('partner_charities');  // removed
//           $table->string('secondary_partner_charities'); // removed

            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('charity_custom_title')->nullable(); // replaces main_charity_ct
            $table->string('primary_partner_charities_custom_title')->nullable(); // replaces partner_charities_ct
            $table->string('secondary_partner_charities_custom_title')->nullable(); // replaces secondary_partner_charities_ct
            $table->timestamps();
        });

        /**
         * OTHER CHANGES TO CONSIDER WHEN IMPORTING DATA
         * The events (array) saved under the partner_charities and secondary_partner_charities attributes have been moved to the charity_charity_listing table.
         */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('charity_listings');
    }
};
