<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    public function up()
    {
       Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->string('company_name');

    $table->string('mobile')->unique();
    $table->string('telephone')->nullable();
    $table->string('fax_no')->nullable();
    $table->string('website')->nullable();

    $table->string('email')->unique();

    $table->text('address')->nullable();
    $table->string('address_line_2')->nullable();
    $table->string('country')->nullable();

    $table->string('language')->default('English');

    $table->string('pan_number')->nullable();
    $table->string('vat_number')->nullable();

    $table->string('logo_path')->nullable();
    $table->string('signature_path')->nullable();

    // 🔥 FIX
    $table->unsignedInteger('selected_user_limit')->default(1);

    $table->enum('status', ['active','blocked'])
          ->default('active');

    $table->date('expiry_date')->nullable();

    $table->timestamps();
});
    }

    public function down()
    {
        Schema::dropIfExists('companies');
    }
}