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
        Schema::create('person_team', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('person_id');
            $table->unsignedBigInteger('team_id');
            $table->string('position')->nullable();
            $table->integer('shirt_number')->nullable();
            $table->bigInteger('market_value')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_until')->nullable();
            $table->string('role')->default('PLAYER'); // PLAYER, COACH, STAFF
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('persons');
            $table->foreign('team_id')->references('id')->on('teams');

            $table->unique(['person_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('person_team');
    }
};
