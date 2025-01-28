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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            // $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // // Removed in favour of the polymorphic relationship  (CanHaveInvoiceableResource) and the Participant model was used (and the seeders updated to handle the migration) instead of the User model
            $table->morphs('invoiceable');
            $table->uuid('ref');
            // $table->foreignId('charity_id')->nullable()->constrained()->nullOnDelete(); // invoices can be created for charities without being linked to a charity_partner_package // Removed in favour of the polymorphic relationship  (CanHaveInvoiceableResource)
            // $table->foreignId('charity_partner_package_id')->nullable()->constrained('charity_partner_package')->nullOnDelete(); // Removed in favour of the polymorphic relationship  (CanHaveInvoiceableResource)
            $table->string('po_number')->nullable();
            $table->string('name');
            $table->string('description')->nullable();
            // $table->string('type')->default('participant_registration'); // replaces category because it is explicit. ['event_places', 'participant_registration', 'charity_membership', 'market_resale', 'partner_package_assignment', 'corporate_credit' ...] // Moved to invoice_items table
            $table->string('charge_id')->nullable();
            $table->string('refund_id')->nullable();
            $table->decimal('discount', 5, 2)->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('price', 16, 2);
            // $table->string('pdf')->nullable(); // implement CanHaveUploadableResource
            $table->string('status'); // ['paid', 'unpaid']
            $table->boolean('held')->default(0);
            $table->date('send_on')->nullable();
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
        Schema::dropIfExists('invoices');
    }
};
