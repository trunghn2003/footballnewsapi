<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_id');
            $table->unsignedBigInteger('season_id');
            $table->integer('matchday');
            $table->string('stage');
            $table->string('type');
            $table->string('group')->nullable();
            $table->unsignedBigInteger('team_id');
            $table->integer('position');
            $table->integer('played_games');
            $table->string('form')->nullable();
            $table->integer('won');
            $table->integer('draw');
            $table->integer('lost');
            $table->integer('points');
            $table->integer('goals_for');
            $table->integer('goals_against');
            $table->integer('goal_difference');
            $table->timestamps();

            $table->foreign('competition_id')->references('id')->on('competitions');
            $table->foreign('season_id')->references('id')->on('seasons');
            $table->foreign('team_id')->references('id')->on('teams');
            
            // Thêm unique index để đảm bảo không có bản ghi trùng lặp
            $table->unique(['competition_id', 'season_id', 'matchday', 'type', 'team_id'], 'unique_standing');
        });
    }

    public function down()
    {
        Schema::dropIfExists('standings');
    }
}; 