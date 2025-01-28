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
        Schema::create('charity_profiles', function (Blueprint $table) { // replaces charity_data
            $table->id();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('ref');
            // $table->text('image_slider'); // Probably not used anymore
            $table->longText('description');
            $table->longText('mission_values');
            $table->string('video')->nullable();
            // $table->string('website')->nullable(); // Removed. It was redundant with the website column on the charities table
            $table->timestamps();
        
            $table->unique(['charity_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('charity_profiles');
    }
};
