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
        Schema::create('charity_listing_ads', function (Blueprint $table) { // replaces partner_listing_ads
            $table->id();
            $table->foreignId('charity_listing_id')->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->smallInteger('key');
            $table->string('position')->default('inline');  // ['inline', 'side']
            $table->string('type');  // ['image', 'video'] // might not be necessary. Use the type column on the uploads table
            // $table->string('path'); // implement CanHaveUploadableResource
            $table->string('link')->nullable();
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
        Schema::dropIfExists('charity_listing_ads');
    }
};
