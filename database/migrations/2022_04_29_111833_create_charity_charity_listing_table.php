<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Contains the charities that are partners (primary, secondary) to a charity (registered under the charity_listings_table).
     * To reduce the database load, only the 2_year charities (associated with the listing) having a custom url are saved under this table.
     * That is, when a charity listing is created and the attribute show_2_year_members is set to 1 (true), instead of filling this table with 2_year charities for every charity_listing (consumes database space), it is more efficient to only save the 2_year charities (for the charity listing) for which the url has been customized. The 2_year charities of a charity listing can then be programmatically queried. Take a look at the twoYearCharities method of the CharityListing model.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charity_charity_listing', function (Blueprint $table) { // replaces charity_partner_listings
            $table->id();
            $table->foreignId('charity_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_page_id')->nullable()->constrained()->nullOnDelete(); // only charity listings created through the listings pages section (not charity listings section) with an event set, have an event page set here.
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('type'); // ['primary_partner', 'secondary_partner', 'two_year'] replaces ['partner', 'secondary', 'two_year']
            $table->string('url')->nullable(); // custom url
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
        Schema::dropIfExists('charity_charity_listing');
    }
};
