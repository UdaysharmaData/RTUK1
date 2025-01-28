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
        Schema::create('event_event_manager', function (Blueprint $table) { // replaces event_manager
            $table->id();
            $table->uuid('ref');
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_manager_id')->constrained()->cascadeOnDelete(); // replaces manager_id
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
        Schema::dropIfExists('event_event_manager');
    }
};
