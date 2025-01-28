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
        Schema::create('attachable_entities', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('site_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('message_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->morphs('attachable');
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
        Schema::dropIfExists('attachable_entities');
    }
};
