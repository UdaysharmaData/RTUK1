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
        Schema::create('event_experience', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('experience_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('value');
            $table->string('description');
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
        Schema::dropIfExists('event_experience');
    }
};
