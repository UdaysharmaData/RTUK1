<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * The events of a campaign.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_event', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        
            $table->unique(['event_id', 'campaign_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_event');
    }
};
