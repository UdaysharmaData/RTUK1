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
        Schema::create('setting_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_id')->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('key'); // [classic_membership_default_places, premium_membership_default_places, partner_membership_default_places, classic_renewal, new_classic_renewal, premium_renewal, new_premium_renewal, two_year_renewal, new_two_year_renewal, partner_renewal, new_partner_renewal]
            $table->string('value')->nullable();
            $table->string('type')->nullable(); // [per_event, all_events, none]. Other membership types use per_event whereas partner membership uses all_events as value given that a partner charity only has access to one registration on all the events on the application. This column helps to reduce the logic in the code since were now make use of the database data. None is for cases where the key/value does not need the type column.
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
        Schema::dropIfExists('setting_custom_fields');
    }
};
