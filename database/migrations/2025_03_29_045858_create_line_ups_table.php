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
        Schema::create('lineups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixture_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('coach_id')->nullable();
            $table->string('formation', 10);


            $table->foreign('fixture_id')
                  ->references('id')
                  ->on('fixtures')
                  ->onDelete('cascade');

            $table->foreign('team_id')
                  ->references('id')
                  ->on('teams')
                  ->onDelete('cascade');

            $table->foreign('coach_id')
                  ->references('id')
                  ->on('persons')
                  ->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lineups');
    }
};