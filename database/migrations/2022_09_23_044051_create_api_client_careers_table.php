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
        Schema::create('api_client_careers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->uuid('ref');
            $table->string('title');
            $table->longText('description');
            $table->string('link')->nullable();
            $table->dateTime('application_closes_at')->nullable();
            $table->json('others')->default(new Expression('(JSON_ARRAY())'));
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('api_client_careers');
    }
};
