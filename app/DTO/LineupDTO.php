<?php

namespace App\DTO;

class LineupDTO implements \JsonSerializable
{
    private int $id;
    private int $fixtureId;
    private int $teamId;
    private string $formation;
    private array $players;

    public function __construct(
        int $id,
        int $fixtureId,
        int $teamId,
        string $formation,
        array $players = []
    ) {
        $this->id = $id;
        $this->fixtureId = $fixtureId;
        $this->teamId = $teamId;
        $this->formation = $formation;
        $this->players = $players;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFixtureId(): int
    {
        return $this->fixtureId;
    }

    public function getTeamId(): int
    {
        return $this->teamId;
    }

    public function getFormation(): string
    {
        return $this->formation;
    }

    public function getPlayers(): array
    {
        return $this->players;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'fixture_id' => $this->fixtureId,
            'team_id' => $this->teamId,
            'formation' => $this->formation,
            'players' => $this->players
        ];
    }
}
