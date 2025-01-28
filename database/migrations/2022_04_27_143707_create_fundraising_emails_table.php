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
        Schema::create('fundraising_emails', function (Blueprint $table) { // replaces drips table
            $table->id();
            $table->uuid('ref');
            $table->boolean('status')->default(1);
            $table->string('name');
            $table->string('subject');
            $table->string('schedule_type')->default('before'); // ['before', 'after']
            $table->smallInteger('schedule_days');
            $table->string('template'); // ['drip1', 'drip2', 'drip3', 'drip4']
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
        Schema::dropIfExists('fundraising_emails');
    }
};
