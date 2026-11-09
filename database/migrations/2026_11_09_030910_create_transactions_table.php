<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('checker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['cash_in', 'cash_out']);
            $table->string('category');
            $table->decimal('amount', 15, 2);
            $table->text('description');
            $table->string('proof_path')->nullable();
            $table->enum('status', ['pending', 'completed', 'rejected'])->default('pending');
            $table->string('tx_hash')->nullable(); // Diisi setelah dicatat ke blockchain
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};