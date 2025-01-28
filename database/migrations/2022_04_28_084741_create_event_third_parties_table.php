<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Third party (like letsdothis, letsdothisrfc) integrations with events.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_third_parties', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partner_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('external_id')->nullable(); // The id corresponding to the event (event equivalence) on a 3rd party platform
            $table->timestamps();

            $table->unique(['event_id', 'partner_channel_id', 'external_id'], 'etp_event_id_partner_channel_id_external_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_third_parties');
    }
};
