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
        Schema::create('fixtures', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('competition_id');
            $table->unsignedBigInteger('season_id');
            $table->dateTime('utc_date');
            $table->string('status');
            $table->integer('matchday')->nullable();
            $table->string('stage')->nullable();
            $table->string('group')->nullable();
            $table->dateTime('last_updated');
            $table->unsignedBigInteger('home_team_id');
            $table->unsignedBigInteger('away_team_id');
            $table->string('winner')->nullable();
            $table->string('duration')->nullable();

            // Scores
            $table->integer('full_time_home_score')->nullable();
            $table->integer('full_time_away_score')->nullable();
            $table->integer('half_time_home_score')->nullable();
            $table->integer('half_time_away_score')->nullable();
            $table->integer('extra_time_home_score')->nullable();
            $table->integer('extra_time_away_score')->nullable();
            $table->integer('penalties_home_score')->nullable();
            $table->integer('penalties_away_score')->nullable();

            $table->string('venue')->nullable();
            $table->timestamps();

            $table->foreign('competition_id')->references('id')->on('competitions');
            $table->foreign('season_id')->references('id')->on('seasons');
            $table->foreign('home_team_id')->references('id')->on('teams');
            $table->foreign('away_team_id')->references('id')->on('teams');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fixtures');
    }
};
