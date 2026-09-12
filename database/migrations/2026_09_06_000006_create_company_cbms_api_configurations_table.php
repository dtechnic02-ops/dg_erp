<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('company_cbms_api_configurations', function(Blueprint $t): void { $t->id(); $t->foreignId('company_id')->unique()->constrained('companies')->cascadeOnDelete(); $t->string('environment',20)->default('test'); $t->string('client_identifier')->nullable(); $t->text('encrypted_credential'); $t->timestamp('last_verified_at')->nullable(); $t->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete(); $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps(); }); }
 public function down(): void { Schema::dropIfExists('company_cbms_api_configurations'); }
};
