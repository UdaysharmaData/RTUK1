<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Database\Traits\ForeignKeyExistsTrait;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use ForeignKeyExistsTrait;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'slug')) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes('events');

                if (array_key_exists("events_slug_unique", $indexesFound))
                    $table->dropUnique(["slug"]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            // $table->unique("slug");
        });
    }
};
