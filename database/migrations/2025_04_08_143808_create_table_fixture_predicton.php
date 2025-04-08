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
        Schema::create('fixture_predictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixture_id');
            $table->json('win_probability');
            $table->json('predicted_score');
            $table->json('key_factors');
            $table->integer('confidence_level');
            $table->text('raw_response');
            $table->json('analysis_data');
            $table->timestamps();
            
            $table->foreign('fixture_id')->references('id')->on('fixtures')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixture_predictions');
    }
};