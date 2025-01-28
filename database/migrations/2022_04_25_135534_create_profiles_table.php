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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('username')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->nullable();
            $table->string('nationality')->nullable();
            $table->string('occupation')->nullable();
            $table->string('passport_number')->nullable();
            $table->longText('bio')->nullable();
//            $table->string('club')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

//            $table->string('company');
//            $table->integer('role_id'); // refactor to a many-to-many role_user relationship
//            $table->integer('default_site_id');
//            $table->string('stripe_id'); // move to user config/profile model class
//            $table->boolean('participant_authorised')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profiles');
    }
};
