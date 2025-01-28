<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Contains the membership of charities.
     * Every charity should only have one active (status = 1) type (membership_type).
     * Keeps records of all the membership type subscriptions of every charity.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charity_memberships', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // ['two_year', 'premium', 'partner', 'classic']
            $table->boolean('status')->default(1);
            $table->boolean('use_new_membership_fee')->default(0);
            $table->date('renewed_on');
            // $table->date('start_date'); // moved to an accessor
            $table->date('expiry_date');
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
        Schema::dropIfExists('charity_memberships');
    }
};
