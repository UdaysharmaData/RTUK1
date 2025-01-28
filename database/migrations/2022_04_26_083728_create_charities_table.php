<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * The charities on the application
     * 
     * The manager_call_notes contains the things the account manager has done with and for the charity.
     * Things like, - Had a meeting the Fredie, - We went over the local pages & also the virtual events,
     * - Freddie gave lots of advice on how to get the best from the membership. etc
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_category_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('ref');
            $table->boolean('status')->default(1);
            $table->string('name'); // replaces title
            $table->string('slug')->unique(); // replaces url
            $table->string('email')->nullable();
            // $table->string('logo')->nullable(); // replaces image. implement CanHaveManyUploadableResource
            $table->string('phone')->nullable();
            // $table->string('address')->nullable(); // implement CanHaveLocationableResource
            $table->string('postcode')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('primary_color')->nullable(); // replaces color_header
            $table->string('secondary_color')->nullable(); // replaces color2_header
            // $table->string('social_facebook')->nullable();   // implement CanHaveManySocialableResource
            // $table->string('social_twitter')->nullable();    // implement CanHaveManySocialableResource
            // $table->string('social_instagram')->nullable();  // implement CanHaveManySocialableResource
            $table->string('website')->nullable();
            $table->string('supporters_video')->nullable(); // Probably not used anymore
            $table->string('donation_link')->nullable(); // replaces link_donate
            $table->boolean('show_in_external_feeds')->default(0);
            $table->boolean('show_in_vmm_external_feeds')->default(0);
            $table->text('external_strapline')->nullable();
            $table->unsignedBigInteger('charity_checkout_id')->nullable(); // replaces cc_id
            $table->boolean('charity_checkout_integration')->default(1); // replaces cc_integration_disabled
            $table->boolean('fundraising_emails_active')->default(0); // replaces drips_active
            $table->string('complete_notifications')->default('always'); // ['always', 'weekly', 'monthly']
            $table->string('external_enquiry_notification_settings')->default('each'); // ['each', 'daily', 'weekly', 'monthly'] // moved from the users table
            $table->string('fundraising_platform')->nullable();
            $table->string('fundraising_platform_url')->nullable();
            $table->string('fundraising_ideas_url')->nullable();
            $table->string('finance_contact_name')->nullable();
            $table->string('finance_contact_email')->nullable();
            $table->string('finance_contact_phone')->nullable();
            $table->text('manager_call_notes')->nullable();
            $table->string('manager_call_status')->nullable(); // ['made_contact', 'no_answer'] // This attribute doesn't seem to be used in the code. It might be removed later during implementation.
                                                  // take a look at the status attribute of the call_notes table
            $table->timestamps();
            $table->softDeletes();
        });

        /**
         * OTHER CHANGES TO CONSIDER WHEN IMPORTING DATA
         * The membership_type, old_membership_type, use_new_membership_fee, expiry_date, old_expiry_date, renewed_on attributes have been revised and moved to the charity_memberships table.
         * The video, video_id, description, and mission_values have been revised and moved to the charity_profiles table.
         */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('charities');
    }
};
