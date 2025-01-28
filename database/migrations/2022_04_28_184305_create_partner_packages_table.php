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
        Schema::create('partner_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('ref');
            $table->string('name');
            $table->decimal('price', 16, 2);
            $table->integer('quantity');
            $table->date('start_date'); // changed type from datetime to date
            $table->date('end_date'); // // changed type from datetime to date
            $table->date('renewal_date')->nullable();
            $table->text('description')->nullable();
            // $table->string('image')->nullable(); // implement CanHaveUploadableResource
            $table->decimal('price_commission', 5, 2)->nullable();
            $table->decimal('renewal_commission', 5, 2)->nullable();
            $table->decimal('new_business_commission', 5, 2)->nullable();
            $table->decimal('partner_split_after_commission', 5, 2)->nullable();
            $table->decimal('rfc_split_after_commission', 5, 2)->nullable();
            $table->timestamp('renewed_at')->nullable(); // Might not be used. Would be checked during implementation
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
        Schema::dropIfExists('partner_packages');
    }
};
