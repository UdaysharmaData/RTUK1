<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A track of actions performed by the participant and some users with higher priviledges on the participant record.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('participant_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // The user that performed the action
            $table->foreignId('role_id')->nullable()->constrained()->cascadeOnDelete(); // Their role while performing the action
            $table->uuid('ref');
            $table->string('type')->nullable(); // ['added', 'deleted'] // The action performed
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
        Schema::dropIfExists('participant_actions');
    }
};
