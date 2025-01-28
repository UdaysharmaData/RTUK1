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
        Schema::create('event_event_category', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('local_fee', 16, 2)->nullable();
            $table->decimal('international_fee', 16, 2)->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('registration_deadline')->nullable();
            $table->dateTime('withdrawal_deadline')->nullable();
            $table->integer('total_places')->unsigned()->nullable(); // The total number of participants an event want.
            $table->integer('classic_membership_places')->unsigned()->nullable();
            $table->integer('premium_membership_places')->unsigned()->nullable();
            $table->integer('two_year_membership_places')->unsigned()->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'event_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_event_category');
    }
};
