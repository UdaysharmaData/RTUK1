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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('ongoing_external_transaction_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->uuid('ref');
            $table->morphs('transactionable'); // [Invoice::class, InvoiceItem::class, Account::class], // InvoiceItem records only get added for refund payments
            $table->string('email')->nullable();
            $table->string('status')->nullable(); // [pending, failed, completed, refunded]
            $table->string('type')->nullable(); // [topup, withdrawal, allocation (for charity membership), payment, refund, participant-transfer, transfer]
            $table->decimal('amount', 16, 2); // The amount of the service/product.
            $table->decimal('fee', 16, 2)->nullable(); // Any extra fee associated with the transaction. Participant transfer fee, Late payment fee, etc.
            $table->string('payment_method')->nullable(); // [Cards, Paypal, Google Pay, Apple Pay, Bacs Debit, Link and Wallet (for local payments)]
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
