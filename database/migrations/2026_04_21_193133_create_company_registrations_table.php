<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_registrations', function (Blueprint $table) {
            $table->id();

            $table->string('company_name');
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('mobile_no')->nullable();
            $table->string('country')->nullable();
            $table->integer('selected_user_limit')->default(5);
            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_registrations');
    }
};