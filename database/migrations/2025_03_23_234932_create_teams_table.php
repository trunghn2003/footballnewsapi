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
        Schema::create('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('area_id');
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('tla', 3)->nullable();
            $table->string('crest')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->integer('founded')->nullable();
            $table->string('club_colors')->nullable();
            $table->string('venue')->nullable();
            $table->dateTime('last_updated');
            $table->timestamps();

            $table->foreign('area_id')->references('id')->on('areas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('teams');
    }
};
