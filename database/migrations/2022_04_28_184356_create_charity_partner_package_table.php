<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Assigns a partner package to a charity
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charity_partner_package', function (Blueprint $table) { // replaces assigned_partner_packages table
            $table->id();
            $table->foreignId('partner_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('ref');
            $table->string('status'); // ['assigned', 'paid]
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
        Schema::dropIfExists('charity_partner_package');
    }
};
