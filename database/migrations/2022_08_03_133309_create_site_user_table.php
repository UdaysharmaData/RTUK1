<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These are roles that belong to a given site. This helps to make the application more flexible and dynamic.
     * Not all roles are entitled to a site. The ones entitled to a site are mostly administrative roles.
     * Administrators having access to the General site are identified/considered as general administrators.
     * Only the general administrator has access to more than one site.
     * The general administrator will be responsible for assigning website administrators to specific sites.
     * The general administrator can create users with any role. But the website administrators will likely be able to only create participants for their respective sites.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_user', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref');
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['site_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_user');
    }
};
