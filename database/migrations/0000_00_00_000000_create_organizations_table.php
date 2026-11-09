<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('balance', 15, 2)->default(0); // Cache saldo agar dashboard cepat
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};