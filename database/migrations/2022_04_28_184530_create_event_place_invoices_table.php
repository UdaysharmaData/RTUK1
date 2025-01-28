<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * At the end of every business period (after every 3 months - from the period attribute), a summary of the event places used by every charity (in all the events) will be documented in a report and an invoice mailed to their finance contact. The places:summary command is responsible for this. 
     * This table is an extension of the invoices table and saves data for which the invoice type is event_places (the invoice created at the end of every business period).
     * The status indicated whether the invoice has been paid or not.
     * 
     * The command places:invoiceReminder checks unpaid event place invoices (event_place_invoices table) everyday and sends a reminder to the charity's finance contact every two weeks. The attribute invoice_sent_on keeps the last date and time when the reminder was sent.
     * 
     * @return void
     */
    public function up()
    {
        Schema::create('event_place_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            // $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete(); // Removed in favour of the morph relationship (CanHaveInvoiceableResource)
            $table->uuid('ref');
            $table->year('year');
            $table->string('period'); // ['03_05', '06_08', '09_11', '12_02']
            $table->string('status'); // ['paid, 'unpaid'] // redundant. Remove this status and use that of the invoice.
            $table->dateTime('invoice_sent_on'); // the date when the invoice was last sent to the charity as a reminder.
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
        Schema::dropIfExists('event_place_invoices');
    }
};
