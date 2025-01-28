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
        Schema::create('customize_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')
                ->nullable()
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->uuid('ref');
            $table->string('name');
            $table->string('slug');
            $table->unsignedTinyInteger('status')->default(0);
            $table->json('chunks')->nullable();
            $table->mediumText('html_content')->nullable();
            $table->timestamp('drafted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['site_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customize_pages');
    }
};
