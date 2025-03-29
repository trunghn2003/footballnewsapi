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
        Schema::create('lineup_players', function (Blueprint $table) {
            $table->unsignedBigInteger('lineup_id');
            $table->unsignedBigInteger('player_id');
            $table->string('position', 5)->nullable();
            $table->integer('shirt_number')->nullable();
            $table->string('grid_position', 5)->nullable();
            $table->boolean('is_substitute')->default(false);
            $table->timestamps();

            $table->primary(['lineup_id', 'player_id']);

            $table->foreign('lineup_id')
                  ->references('id')
                  ->on('lineups')
                  ->onDelete('cascade');

            $table->foreign('player_id')
                  ->references('id')
                  ->on('persons')
                  ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lineup_players');
    }
};