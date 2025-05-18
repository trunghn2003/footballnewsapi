<?php

namespace App\DTO;

class ScoreDTO implements \JsonSerializable
{
    private ?string $winner;
    private ?string $duration;
    private ?array $fullTime;
    private ?array $halfTime;

    private ?array $extraTime;
    private ?array $penalties;

    public function __construct(
        ?string $winner,
        string $duration,
        array $fullTime,
        array $halfTime,
        array $extraTime,
        array $penalties
    ) {
        $this->winner = $winner;
        $this->duration = $duration;
        $this->fullTime = $fullTime;
        $this->halfTime = $halfTime;
        $this->extraTime = $extraTime;
        $this->penalties = $penalties;
    }

    public function getWinner(): ?string
    {
        return $this->winner;
    }

    public function getDuration(): string
    {
        return $this->duration;
    }

    public function getFullTime(): array
    {
        return $this->fullTime;
    }

    public function getHalfTime(): array
    {
        return $this->halfTime;
    }

    public function getExtraTime(): array
    {
        return $this->extraTime;
    }
    public function getPenalties(): array
    {
        return $this->penalties;
    }



    public function jsonSerialize(): array
    {
        return [
            'winner' => $this->winner,
            'duration' => $this->duration,
            'fullTime' => $this->fullTime,
            'halfTime' => $this->halfTime,
            'extraTime' => $this->extraTime,
            'penalties' => $this->penalties
        ];
    }
}
