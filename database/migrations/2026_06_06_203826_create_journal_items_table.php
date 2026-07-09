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
        Schema::create('journal_items', function (Blueprint $table) {

$table->id();

$table->unsignedBigInteger(
'company_id'
);

$table->unsignedBigInteger(
'journal_id'
);

$table->unsignedBigInteger(
'account_id'
);

$table->enum(
'type',
[
'debit',
'credit'
]
);

$table->decimal(
'amount',
18,
2
)->default(0);

$table->longText(
'note'
)->nullable();

$table->tinyInteger(
'status'
)->default(1);

$table->timestamps();


$table->foreign(
'journal_id'
)

->references(
'id'
)

->on(
'journals'
)

->cascadeOnDelete();


$table->foreign(
'account_id'
)

->references(
'id'
)

->on(
'accounts'
)

->cascadeOnDelete();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_items');
    }
};
