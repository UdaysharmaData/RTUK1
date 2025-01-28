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
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('site_id')->nullable()->index()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('type'); // the file type [image, video, pdf etc]
            $table->string('url');
            $table->json('metadata')->nullable(); // Todo: is a metadata field required?
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // NB: The charity_images table has been deleted in favour of the uploads table and it's use_as value set to images (Charity: logo, images).
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('uploads');
    }
};
