<?php

namespace App\Mappers;

use App\DTO\StandingDTO;
use App\Models\Standing;

class StandingMapper
{
    public function toDTO(Standing $standing): StandingDTO
    {
        return new StandingDTO(
            id: $standing->id,
            position: $standing->position,
            playedGames: $standing->played_games,
            won: $standing->won,
            draw: $standing->draw,
            lost: $standing->lost,
            points: $standing->points,
            goalsFor: $standing->goals_for,
            goalsAgainst: $standing->goals_against,
            goalDifference: $standing->goal_difference,
            form: $standing->form,
            teamId: $standing->team->id,
            teamName: $standing->team->name,
            teamShortName: $standing->team->short_name,
            teamTla: $standing->team->tla,
            teamCrest: $standing->team->crest
        );
    }

    public function toDTOs(array $standings): array
    {
        return array_map(fn($standing) => $this->toDTO($standing), $standings);
    }
}
