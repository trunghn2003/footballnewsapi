<?php

namespace App\Mapper;

use App\DTO\ScoreDTO;
use App\Models\Fixture;

class ScoreMapper
{
    public static function fromModel(Fixture $fixture): ScoreDTO
    {
        return new ScoreDTO(
            $fixture->winner,
            $fixture->duration,
            [
                'home' => $fixture->full_time_home_score,
                'away' => $fixture->full_time_away_score
            ],
            [
                'home' => $fixture->half_time_home_score,
                'away' => $fixture->half_time_away_score
            ],
            [
                'home' => $fixture->extra_time_home_score,
                'away' => $fixture->extra_time_away_score
            ],
            [
                'home' => $fixture->penalties_home_score,
                'away' => $fixture->penalties_away_score
            ]
        );
    }

    public static function fromArray(array $data): ScoreDTO
    {
        return new ScoreDTO(
            $data['winner'] ?? null,
            $data['duration'],
            $data['fullTime'],
            $data['halfTime'],
            $data['extraTime'] ?? null,
            $data['penalties'] ?? null
        );
    }
}
