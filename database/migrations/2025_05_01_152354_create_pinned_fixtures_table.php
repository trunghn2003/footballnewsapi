<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pinned_fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('fixture_id')->constrained('fixtures')->onDelete('cascade');
            $table->boolean('notify_before')->default(true);
            $table->boolean('notify_result')->default(true);
            $table->timestamps();

            // Unique constraint to prevent duplicate pins
            $table->unique(['user_id', 'fixture_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pinned_fixtures');
    }
};
