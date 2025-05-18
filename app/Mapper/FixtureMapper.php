<?php

namespace App\Mapper;

use App\DTO\FixtureDTO;
use App\Models\Fixture;

class FixtureMapper
{
    public static function fromModel(Fixture $fixture): FixtureDTO
    {
        return new FixtureDTO(
            $fixture->id,
            $fixture->utc_date->format('Y-m-d\TH:i:s\Z'),
            $fixture->status,
            $fixture->matchday ?? 1,
            $fixture->stage,
            $fixture->group,
            // $fixture->last_updated->format('Y-m-d\TH:i:s\Z'),
            // TeamMapper::fromModel($fixture->homeTeam),
            // TeamMapper::fromModel($fixture->awayTeam),
            ScoreMapper::fromModel($fixture),
        );
    }

    public static function fromArray(array $data): FixtureDTO
    {
        return new FixtureDTO(
            $data['id'],
            $data['utcDate'],
            $data['status'],
            $data['matchday'],
            $data['stage'],
            $data['group'] ?? null,
            // $data['lastUpdated'],
            TeamMapper::fromArray($data['homeTeam']),
            TeamMapper::fromArray($data['awayTeam']),
            ScoreMapper::fromArray($data['score']),
        );
    }
}
