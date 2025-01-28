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
        Schema::create('sitemaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('class_name');
            $table->timestamp('latest_updated_at')->nullable(); // The last time the sitemap was updated with recent records
            $table->timestamp('oldest_updated_at')->nullable(); // The last time the sitemap was dump and regenerated
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
        Schema::dropIfExists('sitemaps');
    }
};
