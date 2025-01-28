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
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'charge_id')) {
                $table->dropColumn('charge_id');
            }

            if (Schema::hasColumn('invoices', 'refund_id')) {
                $table->dropColumn('refund_id');
            }

            if (! Schema::hasColumn('invoices', 'state')) {
                $table->string('state')->after('status'); // [complete, incomplete]. If all the items in the invoice are paid, then the invoice is complete. Otherwise it is incomplete.
            }

            if (Schema::hasColumn('invoices', 'description')) {
                $table->text('description')->nullable()->change();
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
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'charge_id')) {
                $table->string('charge_id')->nullable()->after('description');
            }

            if (! Schema::hasColumn('invoices', 'refund_id')) {
                $table->string('refund_id')->nullable()->after('charge_id');
            }

            if (Schema::hasColumn('invoices', 'state')) {
                $table->dropColumn('state');
            }

            // if (Schema::hasColumn('invoices', 'description')) {
            //     $table->string('description')->nullable()->change(); // Changing this to string will cause an exception as the size of the data inserted with the type text will not fit in type string
            // }
        });
    }
};
