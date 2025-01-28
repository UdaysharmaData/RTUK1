<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores extra information for users having the role participant.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('participant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->unique()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('fundraising_url')->nullable();
            $table->string('slogan')->nullable();
            $table->string('club')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('tshirt_size')->nullable();
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
        Schema::dropIfExists('participant_profiles');
    }
};
