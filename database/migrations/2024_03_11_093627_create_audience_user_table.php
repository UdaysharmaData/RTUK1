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
        Schema::create('audience_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('audience_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['site_id', 'audience_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audience_users');
    }
};
