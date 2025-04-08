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
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('fixture_id')->constrained()->onDelete('cascade');
            $table->string('bet_type'); // WIN, DRAW, LOSS, SCORE
            $table->json('predicted_score')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('odds', 10, 2);
            $table->decimal('potential_win', 10, 2);
            $table->string('status'); // PENDING, WON, LOST
            $table->string('result')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
}; 