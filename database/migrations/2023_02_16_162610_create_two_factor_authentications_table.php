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
    public function up(): void
    {
        if (! Schema::hasTable('two_factor_authentications')) {
            Schema::create('two_factor_authentications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('shared_secret');
                $table->string('label');
                $table->unsignedTinyInteger('digits')->default(6);
                $table->unsignedTinyInteger('seconds')->default(30);
                $table->unsignedTinyInteger('window')->default(0);
                $table->string('algorithm', 16)->default('sha1');
                $table->text('recovery_codes')->nullable();
                $table->timestampTz('recovery_codes_generated_at')->nullable();
                $table->json('safe_devices')->nullable();
                $table->timestampsTz();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_authentications');
    }
};
