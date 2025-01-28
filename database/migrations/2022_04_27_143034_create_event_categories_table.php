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
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('ref');
            $table->string('name');
            $table->string('slug'); // replaces url
            $table->mediumText('description')->nullable();
            // $table->string('type')->nullable(); // removed
            // $table->string('image')->nullable(); // implement CanHaveUploadableResource
            $table->string('color')->nullable();
            $table->double('distance_in_km', 8, 4)->unsigned()->nullable();
            $table->timestamps();

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
        Schema::dropIfExists('event_categories');
    }
};
