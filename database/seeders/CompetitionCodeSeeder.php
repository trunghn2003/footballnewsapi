<?php

namespace Database\Seeders;

use App\Models\Competition;
use Illuminate\Database\Seeder;

class CompetitionCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $competitions = [
            ['id' => 2001, 'code' => 'PL'], // Premier League
            ['id' => 2002, 'code' => 'BL1'], // Bundesliga
            ['id' => 2014, 'code' => 'PD'], // La Liga
            ['id' => 2015, 'code' => 'FL1'], // Ligue 1
            ['id' => 2019, 'code' => 'SA'], // Serie A
            ['id' => 2021, 'code' => 'BSA'], // Serie B
        ];

        foreach ($competitions as $competition) {
            Competition::where('id', $competition['id'])->update([
                'is_featured' => true
            ]);
        }
    }
}
