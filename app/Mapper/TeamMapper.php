<?php

namespace App\Mapper;

use App\DTO\TeamDTO;
use App\Models\Team;

class TeamMapper
{
    public static function fromModel(Team $team): TeamDTO
    {
        return new TeamDTO(
            $team->id,
            $team->name,
            $team->short_name,
            $team->tla,
            $team->crest
        );
    }

    public static function fromArray(array $data): TeamDTO
    {
        return new TeamDTO(
            $data['id'],
            $data['name'],
            $data['shortName'],
            $data['tla'],
            $data['crest']
        );
    }
}
