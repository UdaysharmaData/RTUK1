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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->index()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('audience_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->uuid('ref')->unique();
            $table->string('subject');
            $table->longText('body');
            $table->date('scheduled_at')->nullable();
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
        Schema::dropIfExists('messages');
    }
};
