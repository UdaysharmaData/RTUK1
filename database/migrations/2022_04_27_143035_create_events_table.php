<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * The events run by charities on the application
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) { // replaces charity_events
            $table->id();
            // $table->foreignId('site_id')->constrained()->restrictOnDelete(); // the event_event_category table can help determine on which sites an event is available.
            // $table->foreignId('charity_id')->constrained()->restrictOnDelete(); // Not used anymore
            // $table->foreignId('event_category_id')->constrained()->restrictOnDelete(); // replaces category_id. Moved to event_event_category table
            // $table->string('location')->nullable(); // implement CanHaveLocationableResource
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('ref');
            $table->boolean('status')->default(1);
            $table->string('name'); // replaces title
            $table->string('slug')->unique(); // replaces url
            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->nullable();
            // $table->dateTime('start_date'); // moved to event_event_category table
            // $table->dateTime('end_date'); // moved to event_event_category table
            $table->longText('description')->nullable();
            // $table->string('image')->nullable(); // stores url. Implement CanHaveManyUploadableResource
            $table->string('video')->nullable(); // stores url.
            $table->string('website')->nullable();
            $table->string('review')->nullable(); // stores url
            // $table->string('gallery')->nullable(); // [2,34,53] Implement CanHaveManyUploadableResource.
            $table->boolean('estimated')->default(0); // Whether the event dates are confirmed or estimated. An event whose date is not yet certain/confirmed but the dates set (start_date, end_date etc) are the ones within which the event often take place.
            // $table->string('social_twitter')->nullable(); // replaces twitter. Implement CanHaveSocialbleResource
            // $table->decimal('cost', 8,2); // Removed since saves the same value as the registration_price
            $table->boolean('reg_preferred_heat_time')->default(0);
            $table->boolean('reg_raced_before')->default(0); // replaces reg_participant_raced_before
            $table->boolean('reg_estimated_finish_time')->default(0);
            $table->boolean('reg_tshirt_size')->default(0);
            $table->boolean('reg_age_on_race_day')->default(0);
            $table->boolean('reg_gender')->default(0);
            $table->boolean('reg_dob')->default(0);
            $table->boolean('reg_month_born_in')->default(0);
            $table->boolean('reg_nationality')->default(0);
            $table->boolean('reg_occupation')->default(0);
            $table->boolean('reg_address')->default(0); // Renamed from reg_address_1
            // $table->boolean('reg_address_2')->default(0); // Removed
            $table->boolean('reg_city')->default(0);
            $table->boolean('reg_region')->default(0); // replaces reg_county
            $table->boolean('reg_postcode')->default(0);
            $table->boolean('reg_country')->default(0);
            $table->boolean('reg_phone')->default(0);
            $table->boolean('reg_emergency_contact_name')->default(0);
            $table->boolean('reg_emergency_contact_phone')->default(0); // replaces reg_emergency_contact_telephone
            $table->unsignedSmallInteger('reg_minimum_age')->nullable();
            $table->boolean('reg_family_registrations')->default(0);
            $table->boolean('reg_passport_number')->default(0);
            $table->date('born_before')->nullable();
            // $table->string('distance')->nullable(); // Not needed anymore
            $table->string('custom_preferred_heat_time_start')->nullable();
            $table->string('custom_preferred_heat_time_end')->nullable();
            // $table->decimal('registration_price', 16, 2)->nullable(); // moved to event_event_category table and renamed to local_fee
            // $table->decimal('non_uk_registration_price', 16, 2)->nullable(); // moved to event_event_category table and renamed to international_fee
            // $table->dateTime('registration_deadline')->nullable(); // moved to event_event_category table
            // $table->dateTime('withdrawal_deadline')->nullable(); // moved to event_event_category table
            // $table->integer('classic_membership_places')->unsigned(); // moved to event_event_category table
            // $table->integer('premium_membership_places')->unsigned(); // moved to event_event_category table
            $table->string('terms_and_conditions')->nullable(); // replaces terms_link
            $table->string('charity_checkout_event_page_id')->nullable(); // replaces cc_event_page_id
            $table->string('charity_checkout_event_page_url')->nullable(); // replaces cc_event_page_url
            $table->decimal('charity_checkout_raised', 16, 2)->nullable(); // replaces cc_raised. Type changed to integer.
            $table->string('charity_checkout_title')->nullable(); // replaces cc_title
            $table->string('charity_checkout_status')->default(0); // replaces cc_status
            $table->boolean('charity_checkout_integration')->default(1); // replaces cc_integration_disabled
            $table->dateTime('charity_checkout_created_at')->nullable(); // replaces cc_created_at .The datatype might be changed to string. Datetime was used because there wasn't an example where this attribute's data is not null in the database
            $table->boolean('fundraising_emails')->default(0); // replaces drips_active
            $table->decimal('resale_price', 16, 2)->nullable(); // Fru said it is probably used.
            // $table->integer('total_places')->unsigned()->nullable(); // replaces ticker. The total number of participants an event want. // Moved to event_event_category
            $table->string('reminder')->default('none'); // ['daily', 'weekly', 'monthly']
            $table->string('type'); // ['standalone', 'rolling']. rolling events are created once and run every year on the platform. Standalone events are run only within the period is was created and set to run.
            $table->boolean('partner_event')->default(0); // replaces partner. Partner events are events we (SMA company) have directly relationship with and can sell places to charities through where as non partner events are events we can take inquiries for charities, not payment. However, charities can setup registration pages for non partner events, add them to regional pages. Set a cost and take a payment through our platform.
            // $table->string('partner_disabled'); // moved to the charity_event table (with the type excluded). These are charities that are not allowed to run the event.
            $table->string('charities')->default('all'); // Only included charities or for all charities except excluded charities [all, included, excluded] replaces only_included_charities & partner_disabled. Some events can be run only by some charities. These charities are under the charity_event table with the type included.
            // $table->boolean('virtual_event')->default(0); // replaces virtual. Is a virtual event (event on the Virtual Marathon Series platform)
            // $table->boolean('rolling_event')->default(0); // Is a rolling event (created once and run every year on the platform). Moved as a value under the type attribute. Removed since we can determine whether an event is available on a given site (sfc, rfc, rr, vms etc) or not with the help of the EventCategory model.
            // $table->boolean('rankings_event')->default(0); // Is a rankings event (event on the RunThroughHub platform). Removed since we can determine whether an event is available on a given site (sfc, rfc, rr, vms etc) or not with the help of the EventCategory model.
            $table->boolean('exclude_charities')->default(0); // replaces exclude. Exclude from Charities
            $table->boolean('exclude_website')->default(0); // Exclude from Website
            $table->boolean('exclude_participants')->default(0); // Exclude from Participants
            // $table->boolean('withdrawable')->default(1); // permit participants to withdraw their registration. Removed in favor of an accessor
            $table->boolean('archived')->default(0); // terminates an event and recreate a new one.
            $table->longText('route_info')->nullable();
            $table->longText('what_is_included')->nullable();
            $table->longText('how_to_get_there')->nullable();
            $table->longText('event_day_logistics')->nullable();
            $table->longText('spectator_info')->nullable();
            $table->longText('kit_list')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        /**
         * OTHER CHANGES TO CONSIDER WHEN IMPORTING DATA
         * The events (array) saved under the partner_disabled attribute have been moved to the charity_event table with excluded as the type value.
         * NB: Do a proper check during implementation to see how the attributes exclude_charities, exclude_website, exclude_participants are used. Discard them if they are not used.
         */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
};
