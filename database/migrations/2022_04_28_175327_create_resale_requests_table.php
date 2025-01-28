<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Requests (offer) made by a charity to purchase part or all of the event places (for a certain event) put on sale by another charity.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resale_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resale_place_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('state')->default('pending'); // ['pending', 'accepted', 'paid', 'cancelled']
            $table->integer('places');
            $table->decimal('unit_price', 16, 2);
            $table->decimal('discount', 5, 2)->nullable();
            // $table->decimal('cost', 16, 2); // no need to store computed value. Go through the functionality
            // $table->decimal('calculated_cost', 16, 2); // no need to store computed value. Go through the functionality
            $table->string('contact_email')->nullable(); // either this or the contact_phone should be required
            $table->string('contact_phone')->nullable();
            $table->text('note')->nullable();
            $table->string('charge_id')->nullable(); // removed. Use that of the invoices table

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
        Schema::dropIfExists('resale_requests');
    }
};
