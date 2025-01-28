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
        Schema::create('event_pages', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('event_id')->constrained()->cascadeOnDelete(); // moved to event_event_page
            $table->foreignId('charity_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('corporate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('slug')->nullable()->unique(); // take a look at the slug and the code attributes during implementation and see whether it is necessary to remove one of them.
            // $table->string('charities')->nullable(); // removed. Might not be used anymore.
            $table->string('description')->nullable(); // replaces form_text
            $table->boolean('hide_helper')->default(0);
            $table->string('fundraising_title')->nullable(); // replaces helper_title
            $table->longText('fundraising_description')->nullable(); // replaces helper_text
            $table->decimal('fundraising_target', 16, 2)->nullable();
            $table->string('fundraising_type')->nullable(); // replaces fundraising_title [minimum-sponsorship, registration-fee]
            // $table->string('fundraising_text')->nullable(); // removed
            $table->boolean('published')->default(0);
            $table->string('code')->nullable();
            $table->boolean('all_events')->default(0); // This helps to avoid saving all the events in the event_event_page table thereby it helps reduce database load/space.
            // $table->string('event_ids')->nullable(); // moved to event_event_page table
            $table->boolean('black_text')->nullable(); // Whether or not to display the fundraising_title & fundraising_target in black
            $table->boolean('hide_event_description')->default(0);
            $table->boolean('reg_form_only')->default(0); // Whether or not to only display the registration form on the event page
            // $table->string('charity_membership_type')->nullable(); // might not be needed. Check again during implementation.
            // $table->string('image')->nullable(); // implement CanHaveUploadableResource
            $table->string('video')->nullable(); // stores url
            // $table->string('background_image')->nullable(); // use the uploadabel trait
            $table->boolean('use_enquiry_form')->default(0);
            $table->string('payment_option')->default('participant');
            $table->decimal('registration_price', 16, 2)->nullable();
            $table->dateTime('registration_deadline')->nullable();
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
        Schema::dropIfExists('event_pages');
    }
};
