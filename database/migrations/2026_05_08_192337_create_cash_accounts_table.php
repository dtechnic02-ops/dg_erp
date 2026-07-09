```php id="v5m8ra"
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_accounts', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('company_id');

            $table->string('account_name');

            $table->string('account_number')
                ->nullable();

            $table->decimal(
                'opening_balance',
                15,
                2
            )->default(0);

            $table->text('note')
                ->nullable();

            $table->enum('status', [
                'active',
                'inactive'
            ])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_accounts');
    }
};

