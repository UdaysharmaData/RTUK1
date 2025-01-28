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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->morphs('invoice_itemable');
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->uuid('ref');
            $table->string('type')->default('participant_registration'); // replaces category because it is explicit. ['event_places', 'participant_registration', 'charity_membership', 'market_resale', 'partner_package_assignment', 'corporate_credit' ...]
            $table->decimal('discount', 5, 2)->nullable();
            $table->decimal('price', 16, 2);
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
        Schema::dropIfExists('invoice_items');
    }
};
