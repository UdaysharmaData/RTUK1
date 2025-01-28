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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('name');
            $table->string('slug'); // replaces url
            // $table->string('image')->nullable(); // implement CanHaveUploadableResource
            $table->longText('description')->nullable();
            $table->string('website')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['slug', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners');
    }
};
