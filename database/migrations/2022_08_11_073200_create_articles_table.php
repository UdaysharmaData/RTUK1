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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete(); // published by
            $table->foreignId('user_id')->nullable()->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->uuid('ref')->unique();
            $table->string('title');
            $table->string('slug');
            $table->longText('body');
            $table->boolean('is_published')->default(false);
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
        Schema::dropIfExists('articles');
    }
};
