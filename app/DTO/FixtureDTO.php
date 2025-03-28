<?php

class FixtureDTO implements \JsonSerializable
{
    private int $id;
    private string $utc_date;
    private string $status;
    private ?int $matchday;
    private ?string $stage;
    private ?string $group;
    private ?string $winner;
    private ?string $duration;
    private ?int $full_time_home_score;
    private ?int $full_time_away_score;
    private ?int $half_time_home_score;
    private ?int $half_time_away_score;
    private ?int $extra_time_home_score;
    private ?int $extra_time_away_score;
    private ?int $penalties_home_score;
    private ?int $penalties_away_score;
    private ?string $venue;
    private ?string $created_at;
    private ?string $updated_at;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'utc_date' => $this->utc_date,
            'status' => $this->status,
            'matchday' => $this->matchday,
            'stage' => $this->stage,
            'group' => $this->group,

            'winner' => $this->winner,
            'duration' => $this->duration,
            'full_time_home_score' => $this->full_time_home_score,
            'full_time_away_score' => $this->full_time_away_score,
            'half_time_home_score' => $this->half_time_home_score,
            'half_time_away_score' => $this->half_time_away_score,
            'extra_time_home_score' => $this->extra_time_home_score,
            'extra_time_away_score' => $this->extra_time_away_score,
            'penalties_home_score' => $this->penalties_home_score,
            'penalties_away_score' => $this->penalties_away_score,

        ];
    }

    // Getters and setters for all properties
    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUtcDate(): string
    {
        return $this->utc_date;
    }

    public function setUtcDate(string $utc_date): self
    {
        $this->utc_date = $utc_date;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getMatchday(): ?int
    {
        return $this->matchday;
    }

    public function setMatchday(?int $matchday): self
    {
        $this->matchday = $matchday;
        return $this;
    }

    public function getStage(): ?string
    {
        return $this->stage;
    }

    public function setStage(?string $stage): self
    {
        $this->stage = $stage;
        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): self
    {
        $this->group = $group;
        return $this;
    }

    public function getWinner(): ?string
    {
        return $this->winner;
    }

    public function setWinner(?string $winner): self
    {
        $this->winner = $winner;
        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getFullTimeHomeScore(): ?int
    {
        return $this->full_time_home_score;
    }

    public function setFullTimeHomeScore(?int $full_time_home_score): self
    {
        $this->full_time_home_score = $full_time_home_score;
        return $this;
    }

    public function getFullTimeAwayScore(): ?int
    {
        return $this->full_time_away_score;
    }

    public function setFullTimeAwayScore(?int $full_time_away_score): self
    {
        $this->full_time_away_score = $full_time_away_score;
        return $this;
    }

    public function getHalfTimeHomeScore(): ?int
    {
        return $this->half_time_home_score;
    }

    public function setHalfTimeHomeScore(?int $half_time_home_score): self
    {
        $this->half_time_home_score = $half_time_home_score;
        return $this;
    }

    public function getHalfTimeAwayScore(): ?int
    {
        return $this->half_time_away_score;
    }

    public function setHalfTimeAwayScore(?int $half_time_away_score): self
    {
        $this->half_time_away_score = $half_time_away_score;
        return $this;
    }

    public function getExtraTimeHomeScore(): ?int
    {
        return $this->extra_time_home_score;
    }

    public function setExtraTimeHomeScore(?int $extra_time_home_score): self
    {
        $this->extra_time_home_score = $extra_time_home_score;
        return $this;
    }

    public function getExtraTimeAwayScore(): ?int
    {
        return $this->extra_time_away_score;
    }

    public function setExtraTimeAwayScore(?int $extra_time_away_score): self
    {
        $this->extra_time_away_score = $extra_time_away_score;
        return $this;
    }

    public function getPenaltiesHomeScore(): ?int
    {
        return $this->penalties_home_score;
    }

    public function setPenaltiesHomeScore(?int $penalties_home_score): self
    {
        $this->penalties_home_score = $penalties_home_score;
        return $this;
    }

    public function getPenaltiesAwayScore(): ?int
    {
        return $this->penalties_away_score;
    }

    public function setPenaltiesAwayScore(?int $penalties_away_score): self
    {
        $this->penalties_away_score = $penalties_away_score;
        return $this;
    }

    public function getVenue(): ?string
    {
        return $this->venue;
    }

    public function setVenue(?string $venue): self
    {
        $this->venue = $venue;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(?string $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?string $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

}
