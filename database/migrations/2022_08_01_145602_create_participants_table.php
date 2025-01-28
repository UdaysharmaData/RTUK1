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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // $table->foreignId('event_id')->constrained()->restrictOnDelete(); // Changed to event_event_category_id
            $table->foreignId('event_event_category_id')->constrained('event_event_category')->restrictOnDelete();
            $table->foreignId('charity_id')->nullable()->constrained()->nullOnDelete(); // Though participants belong to charities, a participant might register for an event through the registration page of a different charity. Also, in case a participant is transfered to another charity, we should be able to keep tract of it's events participations with his/her former charity. So, this field is necessary on this table.
            $table->foreignId('corporate_id')->nullable()->constrained()->nullOnDelete();
            // $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete(); // Removed in favour of the morph relationship (CanHaveInvoiceableResource)
            $table->string('status'); // [notified, incomplete, complete, clearance]
            $table->string('waive')->nullable();
            $table->string('waiver')->nullable();
            $table->boolean('fully_registered')->default(0);
            // $table->string('title'); // Moved to the profiles table
            // $table->string('first_name'); // Moved to the profiles table
            // $table->string('last_name'); // Moved to the profiles table
            // $table->string('address_1'); // Moved to the profiles table
            // $table->string('address_2'); // Moved to the profiles table
            // $table->string('county'); // Moved to the profiles table and renamed to region
            // $table->string('country'); // Moved to the profiles table
            // $table->string('city'); // Moved to the profiles table
            // $table->string('postcode'); // Moved to the profiles table

            // moved to a exempted column below
            // $table->boolean('exempt')->default(0);
            // $table->boolean('corporate_exempt')->default(0);
            // $table->boolean('external_exempt')->default(0);
            // $table->boolean('partial_exempt')->default(0);

            // $table->string('charge_id')->nullable(); // Removed in favour of the morph relationship (CanHaveInvoiceableResource)
            // $table->string('refund_id')->nullable(); // Removed in favour of the morph relationship (CanHaveInvoiceableResource)
            $table->string('preferred_heat_time')->nullable();
            $table->boolean('raced_before')->default(0); // renamed from participant_raced_before
            $table->time('estimated_finish_time')->nullable();
            // $table->string('tshirt_size')->nullable(); // Keep here or move to participant profiles table
            // $table->string('age_on_race_day')->nullable(); // Changed to an accessor (appended attribute)
            // $table->string('gender')->nullable(); // Moved to profiles table
            // $table->date('dob')->nullable(); // Moved to profiles table
            // $table->string('month_born_in')->nullable(); // Use an accessor for this
            // $table->string('nationality')->nullable(); // Moved to the profiles table.
            // $table->string('occupation')->nullable(); // Moved to the profiles table
            // $table->string('contact_number')->nullable(); // Moved to the profiles table and renamed to phone_number
            // $table->string('mobile')->nullable(); Moved to the profiles table and renamed to mobile_number
            // $table->string('emergency_contact_name')->nullable(); // Moved to the participant_profiles table
            // $table->string('emergency_contact_telephone')->nullable(); // Moved to the participant_profiles table and renamed to emergency_contact_phone

            // $table->boolean('event_terms_conditions'); // Removed
            // $table->boolean('sfc_terms_conditions'); // Removed

            $table->string('fundraising_id')->nullable(); // renamed from pf_id
            $table->string('fundraising_url')->nullable(); // renamed from pf_url

            $table->decimal('charity_checkout_raised', 16, 2)->nullable(); // Type changed from string to decimal. Take a look at the charity_checkout_raised attribute of the events table and see whether it should be removed from the schema and used as an accessor with it's value computed by a query on this table.
            $table->string('charity_checkout_title')->nullable();
            $table->boolean('charity_checkout_status')->default(0);
            $table->timestamp('charity_checkout_created_at')->nullable();
            $table->decimal('how_much_raised', 16, 2)->nullable(); // Replaces how_much_raise
            $table->string('added_via'); // [dashboard, registration_page, charity, external_enquiry_offer, virtual_event_dashboard, virtual_event_team_invitation, vms_website] Replaces added_by
            $table->foreignId('event_page_id')->nullable()->constrained()->nullOnDelete(); // The event page through which the participant registered. This value is required when added_via value is registration_page.
            // $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete(); // Replaces added_by_user_id. The user that registered the participant to the event. Moved to participant_actions table
            // $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete(); // Moved to participant_actions table
            $table->boolean('enable_family_registration')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('participants');
    }
};
