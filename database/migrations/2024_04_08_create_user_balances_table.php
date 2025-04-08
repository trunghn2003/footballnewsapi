<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // DEPOSIT, WITHDRAW, BET, WIN
            $table->decimal('amount', 15, 2);
            $table->string('status'); // PENDING, COMPLETED, FAILED
            $table->string('reference')->nullable(); // Mã giao dịch
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Dữ liệu bổ sung
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('user_balances');
    }
}; 