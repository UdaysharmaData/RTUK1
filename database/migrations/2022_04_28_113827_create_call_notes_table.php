<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Few months before the expiry date of a charity's membership subscription period, the account manager is supposed to enter in contact with the charity owners to renew their membership.
     * Everytime the account manager contacts the charity with regards to renewing their membership, he/she keeps track of that and it is recorded on this table.
     * 
     * The call attribute is the period intervals within which the account manager is notified to enter in contact with the charity (before their membership expiry date arrives).
     * The status attribute indicates the charity response to the message (whether they replied or not)
     * 
     * Refer also to the manager_call_notes on the charities table.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->uuid('ref');
            $table->year('year');
            $table->string('call'); // ['23_months', '21_months', '18_months', '15_months', '12_months', '11_months', '8_months', '5_months', '2_months', '1_month']
            $table->text('note')->nullable();
            $table->string('status'); // ['made_contact', 'no_answer']
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
        Schema::dropIfExists('call_notes');
    }
};
