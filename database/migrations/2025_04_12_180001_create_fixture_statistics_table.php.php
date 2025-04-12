<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fixture_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained('fixtures')->onDelete('cascade');
            $table->string('period'); // ALL, 1ST, 2ND
            $table->string('group_name'); // Match overview, Shots, Attack...
            $table->string('statistic_name'); // Ball possession, Expected goals...
            $table->string('key'); // ballPossession, expectedGoals...
            $table->string('home'); // "32%", "1.52"...
            $table->string('away'); // "68%", "1.62"...
            $table->integer('compare_code'); // 1: home win, 2: away win, 3: draw
            $table->string('statistics_type'); // positive/negative
            $table->string('value_type'); // event/team
            $table->decimal('home_value', 10, 4); // 32, 1.52...
            $table->decimal('away_value', 10, 4); // 68, 1.62...
            $table->integer('home_total')->nullable(); // Ví dụ: 27 trong "13/27 (48%)"
            $table->integer('away_total')->nullable(); // Ví dụ: 27 trong "14/27 (52%)"
            $table->integer('render_type'); // 1-4 tùy theo cách hiển thị
            $table->timestamps();

            // Fixed unique key with custom shorter name
            $table->unique(['fixture_id', 'period', 'group_name', 'statistic_name'], 'fixture_stats_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fixture_statistics');
    }
};
