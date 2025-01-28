<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
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
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('api_client_id')->index();
            $table->foreignId('site_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('name')->unique();
            $table->string('host')->unique()->nullable();
            $table->ipAddress('ip')->unique()->nullable();
            $table->json('blacklist')->default(new Expression('(JSON_ARRAY())'));
            $table->boolean('is_active')->default(false);
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
        Schema::dropIfExists('api_clients');
    }
};
