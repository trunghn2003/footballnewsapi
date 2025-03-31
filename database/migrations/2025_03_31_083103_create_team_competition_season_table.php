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
        Schema::create('team_competition_season', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained('teams');
            $table->foreignId('competition_id')->constrained('competitions');
            $table->foreignId('season_id')->constrained('seasons');
            $table->timestamps();

            $table->primary(['team_id', 'competition_id', 'season_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_competition_season');
    }
};
