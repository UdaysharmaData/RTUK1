<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * The number of places a charity has for an event and want to sell them to charities that might be interested.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resale_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->uuid('ref');
            $table->integer('places');
            $table->integer('taken')->default(0);
            $table->decimal('unit_price', 16, 2);
            $table->decimal('discount', 5, 2)->nullable();
            // $table->boolean('discount_enabled')->default(0); // The discount attribute can handle what this attribute does.
                                                                // It might be removed during implementation
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
        Schema::dropIfExists('resale_places');
    }
};
