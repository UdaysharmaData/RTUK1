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
        Schema::create('event_city_linking', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('ref')->unique(); // Unique reference string
            $table->unsignedBigInteger('site_id'); // Foreign key for sites
            $table->unsignedBigInteger('event_id'); // Foreign key for events
            $table->unsignedBigInteger('city_id'); // Foreign key for regions
            $table->timestamps(); // created_at and updated_at

            // Foreign key constraints
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_city_linking');
    }
};
