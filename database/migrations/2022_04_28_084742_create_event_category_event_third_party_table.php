<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Saves the event category and their equivalences on the third party platform (Lets Do This platform for example) for a given event.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_category_event_third_party', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('event_third_party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('external_id'); // Named as raceId on LDT. It is the/our event category equivalence on LDT third party
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
        Schema::dropIfExists('event_category_event_third_party');
    }
};
