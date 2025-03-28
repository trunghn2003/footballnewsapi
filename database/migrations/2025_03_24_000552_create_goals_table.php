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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixture_id');
            $table->integer('minute');
            $table->integer('extra_time')->nullable();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('scorer_id');
            $table->unsignedBigInteger('assist_id')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();

            $table->foreign('fixture_id')->references('id')->on('fixtures');
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('scorer_id')->references('id')->on('persons');
            $table->foreign('assist_id')->references('id')->on('persons');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goals');
    }
};
