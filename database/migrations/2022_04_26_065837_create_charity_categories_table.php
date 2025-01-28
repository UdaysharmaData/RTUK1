<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * The different categories a charity can belong to.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charity_categories', function (Blueprint $table) { // replaces categories table
            $table->id();
            $table->uuid('ref');
            $table->boolean('status');
            $table->string('name');
            $table->string('slug')->unique(); // replaces url
            // $table->string('image')->nullable(); // implement CanHaveUploadableResource
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
        Schema::dropIfExists('charity_categories');
    }
};
