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
        Schema::create('event_managers', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('complete_notifications')->nullable(); // ['always', 'weekly', 'monthly']
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
        Schema::dropIfExists('event_managers');
    }
};
