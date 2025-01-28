<?php

use App\Modules\Finance\Enums\AccountStatusEnum;
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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->uuid('ref');
            $table->string('status')->default(AccountStatusEnum::Active->value); // [active, inactive]
            $table->string('type'); // [finite, infinite]
            $table->string('name')->nullable(); // Set by the code. Create an enum for that.
            $table->decimal('balance', 16, 2);
            $table->date('valid_from')->nullable(); // Required when type is finite
            $table->date('valid_to')->nullable();  // Required when type is finite
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
        Schema::dropIfExists('accounts');
    }
};
