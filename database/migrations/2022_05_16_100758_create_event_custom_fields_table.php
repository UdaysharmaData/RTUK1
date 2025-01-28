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
        Schema::create('event_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('name');
            $table->string('slug');
            // $table->string('type'); // ['text', 'textarea', 'select', 'radio', 'checkbox'] // not used. The response attribute handles this.
            $table->string('caption')->nullable(); // Renamed from content
            $table->string('type'); // ['text', 'textarea', 'select', 'radio', 'checkbox'] // replaces from response
            $table->string('possibilities')->nullable(); // replaces response_options
            // $table->string('response_values')->nullable(); // Removed
            $table->boolean('status')->default(1);
            $table->string('rule')->default('required'); // [optional, required]
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['event_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_custom_fields');
    }
};
