<?php

namespace App\DTO;

class StandingDTO
{
    public function __construct(
        private int $id,
        private int $position,
        private int $playedGames,
        private int $won,
        private int $draw,
        private int $lost,
        private int $points,
        private int $goalsFor,
        private int $goalsAgainst,
        private int $goalDifference,
        private string $form,
        private int $teamId,
        private string $teamName,
        private string $teamShortName,
        private string $teamTla,
        private string $teamCrest
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'played_games' => $this->playedGames,
            'won' => $this->won,
            'draw' => $this->draw,
            'lost' => $this->lost,
            'points' => $this->points,
            'goals_for' => $this->goalsFor,
            'goals_against' => $this->goalsAgainst,
            'goal_difference' => $this->goalDifference,
            'form' => $this->form,
            'team' => [
                'id' => $this->teamId,
                'name' => $this->teamName,
                'short_name' => $this->teamShortName,
                'tla' => $this->teamTla,
                'crest' => $this->teamCrest
            ]
        ];
    }
}
