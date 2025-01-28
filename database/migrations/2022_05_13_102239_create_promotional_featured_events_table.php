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
        Schema::create('promotional_featured_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete(); // replaces county
            $table->foreignId('event_id')->constrained()->cascadeOnDelete(); // replaces events [2132, 3432, 3245]
            $table->timestamps();

            $table->unique(['region_id', 'event_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promotional_featured_events');
    }
};
