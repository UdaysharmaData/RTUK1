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
        Schema::create('mailing_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->index()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('audience_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->uuid('ref')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'audience_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mailing_lists');
    }
};
