<?php

namespace App\DTO;

class FixtureDTO implements \JsonSerializable
{
    private ?int $id;
    private ?string $utcDate;
    private ?string $status;
    private ?int $matchday;
    private ?string $stage;
    private ?string $group;

    private ?CompetitionDTO $competition =null;

    public ?TeamDTO $homeTeam = null;
    public ?TeamDTO $awayTeam = null;
    private ?ScoreDTO $score;
    // private array $referees;
    private bool $isPinned = false;

    public function __construct(
        int $id,
        string $utcDate,
        string $status,
        int $matchday,
        string $stage,
        ?string $group,
        // TeamDTO $homeTeam,
        // TeamDTO $awayTeam,
        ScoreDTO $score,

        // array $referees
    ) {
        $this->id = $id;
        $this->utcDate = $utcDate;
        $this->status = $status;
        $this->matchday = $matchday;
        $this->stage = $stage;
        $this->group = $group;
        // $this->homeTeam = $homeTeam;
        // $this->awayTeam = $awayTeam;
        $this->score = $score;

        // $this->referees = $referees;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUtcDate(): string
    {
        return $this->utcDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMatchday(): int
    {
        return $this->matchday;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }



    public function getHomeTeam(): TeamDTO
    {
        return $this->homeTeam;
    }

    public function getAwayTeam(): TeamDTO
    {
        return $this->awayTeam;
    }

    public function getScore(): ScoreDTO
    {
        return $this->score;
    }

    public function getHomeLineup(): LineupDTO
    {
        return $this->homeLineup;
    }

    public function getAwayLineup(): LineupDTO
    {
        return $this->awayLineup;
    }

    public function setAwayLineup(?LineupDTO $awayLineup): void
    {
        $this->awayLineup = $awayLineup;
    }

    public function setHomeLineup(?LineupDTO $homeLineup): void
    {
        $this->homeLineup = $homeLineup;
    }
    public function setHomeTeam(TeamDTO $homeTeam): void
    {
        $this->homeTeam = $homeTeam;
    }
    public function setAwayTeam(TeamDTO $awayTeam): void
    {
        $this->awayTeam = $awayTeam;
    }



    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'competition' => $this->competition,
            'utcDate' => $this->utcDate,
            'status' => $this->status,
            'matchday' => $this->matchday,
            'stage' => $this->stage,
            'group' => $this->group,
            'homeTeam' => $this->homeTeam,
            'awayTeam' => $this->awayTeam,
            'score' => $this->score,
            'isPinned' => $this->isPinned,
        ];
    }

    public function getCompetition(): CompetitionDTO
    {
        return $this->competition;
    }

    public function setCompetition(CompetitionDTO $competition): void
    {
        $this->competition = $competition;
    }

    public function getIsPinned(): bool
    {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned): void
    {
        $this->isPinned = $isPinned;
    }
}
