
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations.
     */
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {

            $table->id();

            // 🔥 COMPANY
            $table->unsignedBigInteger('company_id');

            // 🔥 BANK INFO
            $table->string('bank_name');

            $table->string('account_name');

            $table->string('branch')->nullable();

            $table->string('account_no');

            $table->string('swift_code')->nullable();

            // 🔥 OPENING BALANCE
            $table->decimal(
                'opening_balance',
                15,
                2
            )->default(0);

            // 🔥 IMAGE
            $table->string('image_path')->nullable();

            // 🔥 STATUS
            $table->enum('status', [
                'active',
                'inactive'
            ])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};

