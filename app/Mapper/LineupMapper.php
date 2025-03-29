<?php

namespace App\Mapper;

use App\DTO\LineupDTO;
use App\DTO\LineupPlayerDTO;
use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Repositories\PersonRepository;

class LineupMapper
{
    private PersonRepository $personRepository;

    public function __construct(PersonRepository $personRepository)
    {
        $this->personRepository = $personRepository;
    }

    public function toDTO(Lineup $lineup): LineupDTO
    {
//        dd($lineup->lineupPlayers);
        $players = $lineup->lineupPlayers->map(function (LineupPlayer $player) {
            $person = $this->personRepository->findById($player->player_id);
//            dd($player);
            return new LineupPlayerDTO(
//                $player->id,
                $player->lineup_id,
                $player->player_id,
                $player->position,
                $player->grid_position,
                $player->shirt_number,
                $player->is_substitute,
                $person ? PersonMapper::toDTO($person) : null
            );
        })->toArray();

        return new LineupDTO(
            $lineup->id,
            $lineup->fixture_id,
            $lineup->team_id,
            $lineup->formation,
            $players
        );
    }

    public function toModel(LineupDTO $dto): Lineup
    {
        return new Lineup([
            'fixture_id' => $dto->getFixtureId(),
            'team_id' => $dto->getTeamId(),
            'formation' => $dto->getFormation()
        ]);
    }

    public function toLineupPlayerModel(LineupPlayerDTO $dto): LineupPlayer
    {
        return new LineupPlayer([
            'lineup_id' => $dto->getLineupId(),
            'person_id' => $dto->getPlayerId(),
            'position' => $dto->getPosition(),
            'grid_position' => $dto->getGridPosition(),
            'shirt_number' => $dto->getShirtNumber(),
            'is_substitute' => $dto->isSubstitute()
        ]);
    }
}
