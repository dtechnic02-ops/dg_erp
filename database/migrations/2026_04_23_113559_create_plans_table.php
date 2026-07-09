<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            $table->string('name'); // Plan name

            $table->integer('user_limit'); // how many users

            $table->decimal('price', 10, 2)->default(0);

            $table->integer('duration_days'); // 7 / 30 / 365

            $table->enum('type', ['trial','monthly','yearly']);

            $table->boolean('is_active')->default(1); // enable/disable

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};