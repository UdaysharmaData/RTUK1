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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // replaces manager_id. account_manager | event_manager
            $table->uuid('ref');
            $table->string('title');
            $table->string('package'); // ['25_leads', '50_leads', '250_leads', '500_leads', '1000_leads', '2500_leads', '5000_leads', 'classic', 'premium', '2_year']
            $table->string('status'); // ['created', 'active', 'complete', ...]
            // $table->string('events'); // moved to the campaign_event table
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->smallInteger('notification_trigger')->nullable();
            $table->timestamps();
        });

        /**
         * OTHER CHANGES TO CONSIDER WHEN IMPORTING DATA
         * The events (array) saved under the events attribute have been moved to the campaign_event table.
         */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaigns');
    }
};
