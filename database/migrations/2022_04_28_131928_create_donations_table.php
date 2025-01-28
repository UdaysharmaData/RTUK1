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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('corporate_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('ref');
            $table->decimal('amount', 16, 2);  // changed type from double to decimal
            $table->decimal('conversion_rate', 16, 2)->default(1); // changed type from integer to decimal
            $table->date('expires_at')->nullable();
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
        Schema::dropIfExists('donations');
    }
};
