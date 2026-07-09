
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {

            $table->id();

            // 🔥 COMPANY
            $table->unsignedBigInteger('company_id');

            // 🔥 BASIC
            $table->string('name');
            $table->string('authority_name')->nullable();

            // 🔥 CONTACT
            $table->string('mobile')->nullable();
            $table->string('telephone')->nullable();
            $table->string('fax_no')->nullable();

            // 🔥 ONLINE
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // 🔥 ADDRESS
            $table->text('address')->nullable();

            // 🔥 TAX
            $table->string('tax_no')->nullable();

            // 🔥 FINANCE
            $table->decimal('opening_balance', 15, 2)
                  ->default(0);

            // 🔥 BANK
            $table->string('bank_name')->nullable();

            $table->string('bank_account_no')->nullable();

            // 🔥 NOTE
            $table->text('note')->nullable();

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
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};

