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
        // Todo: rename table to `charity_user`
        // Todo: { id, charity_id, user_id, type [owner, manager, user, participant] }

        Schema::create('charity_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('type'); // ['owner', 'manager', 'user', 'participant']
            $table->timestamps();

            $table->unique(['charity_id', 'user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('charity_user');
    }
};
