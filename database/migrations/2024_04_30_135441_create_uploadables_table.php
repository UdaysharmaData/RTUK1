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
        Schema::create('uploadables', function (Blueprint $table) {
            $table->id();
            $table->string('use_as'); // ['logo', 'image', 'images', 'gallery', ...]. Some models need to save images that are used for different purposes say, logo and gallery. The use_as column helps to distinguish these images. For example, the Event model needs to save the event image and gallery and the Charity model needs to save the logo and images. The values of this use_as column are table specific (Event: image, gallery; Charity: logo, images, Profile: image).
            $table->foreignId('upload_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->morphs('uploadable');
            $table->timestamps();

            $table->unique(['use_as', 'upload_id', 'uploadable_id', 'uploadable_type'], 'unique_uploadable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('uploadables');
    }
};
