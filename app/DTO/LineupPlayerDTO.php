<?php

namespace App\DTO;

class LineupPlayerDTO implements \JsonSerializable
{
    private int $lineupId;
    private int $playerId;
    private ?string $position;
    private ?string $gridPosition;
    private ?int $shirtNumber;
    private bool $isSubstitute;
    private ?PersonDTO $player;

    public function __construct(
        int $lineupId,
        int $playerId,
        ?string $position = null,
        ?string $gridPosition = null,
        ?int $shirtNumber = null,
        bool $isSubstitute = false,
        ?PersonDTO $player = null
    ) {

        $this->lineupId = $lineupId;
        $this->playerId = $playerId;
        $this->position = $position;
        $this->gridPosition = $gridPosition;
        $this->shirtNumber = $shirtNumber;
        $this->isSubstitute = $isSubstitute;
        $this->player = $player;
    }



    public function getLineupId(): int
    {
        return $this->lineupId;
    }

    public function getPlayerId(): int
    {
        return $this->playerId;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function getGridPosition(): ?string
    {
        return $this->gridPosition;
    }

    public function getShirtNumber(): ?int
    {
        return $this->shirtNumber;
    }

    public function isSubstitute(): bool
    {
        return $this->isSubstitute;
    }

    public function getPlayer(): ?PersonDTO
    {
        return $this->player;
    }

    public function jsonSerialize(): array
    {
        return [
            'lineup_id' => $this->lineupId,
            'player_id' => $this->playerId,
            'position' => $this->position,
            'grid_position' => $this->gridPosition,
            'shirt_number' => $this->shirtNumber,
            'is_substitute' => $this->isSubstitute,
            'player' => $this->player ? $this->player : null
        ];
    }
}
