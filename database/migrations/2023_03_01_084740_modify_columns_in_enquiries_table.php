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
        Schema::table('enquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('enquiries', 'event_category_id')) {
                $table->foreignId('event_category_id')->after('event_id')->nullable()
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('enquiries', 'participant_id')) {
                $table->foreignId('participant_id')->after('corporate_id')->nullable()
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('enquiries', 'external_enquiry_id')) {
                $table->foreignId('external_enquiry_id')->after('participant_id')->nullable()
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }

            if (Schema::hasColumn('enquiries', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('enquiries', 'how_much_raise')) {
                $table->decimal('how_much_raise', 16, 2)->nullable()->change();
            }

            if (Schema::hasColumn('enquiries', 'name')) {
                $table->string('name')->nullable()->change();
            }

            if (Schema::hasColumn('enquiries', 'how_much_raise')) {
                $table->renameColumn('how_much_raise', 'fundraising_target');
            }

            if (! Schema::hasColumn('enquiries', 'last_name')) {
                $table->string('last_name')->nullable()->after('name');
            }
            
            if (Schema::hasColumn('enquiries', 'name')) {
                $table->renameColumn('name', 'first_name');
            }

            if (Schema::hasColumn('enquiries', 'made_contact')) {
                $table->renameColumn('made_contact', 'contacted');
            }

            if (Schema::hasColumn('enquiries', 'sex')) {
                $table->renameColumn('sex', 'gender');
            }

            if (! Schema::hasColumn('enquiries', 'timeline')) {
                $table->text('timeline')->nullable()->after('comments');
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
        Schema::table('enquiries', function (Blueprint $table) {
            if (Schema::hasColumn('enquiries', 'event_category_id')) {
                $table->dropConstrainedForeignId('event_category_id');
            }

            if (Schema::hasColumn('enquiries', 'participant_id')) {
                $table->dropConstrainedForeignId('participant_id');
            }

            if (Schema::hasColumn('enquiries', 'external_enquiry_id')) {
                $table->dropConstrainedForeignId('external_enquiry_id');
            }
            
            if (! Schema::hasColumn('enquiries', 'status')) {
                $table->string('status')->nullable()->after('postcode'); // ['assigned', 'not-assigned']
            }
            
            if (Schema::hasColumn('enquiries', 'fundraising_target')) {
                $table->renameColumn('fundraising_target', 'how_much_raise');
            }

            if (Schema::hasColumn('enquiries', 'first_name')) {
                $table->renameColumn('first_name', 'name');
            }

            if (Schema::hasColumn('enquiries', 'last_name')) {
                $table->dropColumn('last_name');
            }

            if (Schema::hasColumn('enquiries', 'contacted')) {
                $table->renameColumn('contacted', 'made_contact');
            }

            if (Schema::hasColumn('enquiries', 'gender')) {
                $table->renameColumn('gender', 'sex');
            }

            if (Schema::hasColumn('enquiries', 'timeline')) {
                $table->dropColumn('timeline');
            }
        });
    }
};
