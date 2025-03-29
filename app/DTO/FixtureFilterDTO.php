<?php

namespace App\DTO;

class FixtureFilterDTO
{
    private ?array $competitionIds;
    private ?array $matchIds;
    private ?string $dateFrom;
    private ?string $dateTo;
    private ?string $status;

    public function __construct(
        ?array $competitionIds = null,
        ?array $matchIds = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $status = null
    ) {
        $this->competitionIds = $competitionIds;
        $this->matchIds = $matchIds;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
    }

    public function getCompetitionIds(): ?array
    {
        return $this->competitionIds;
    }

    public function getMatchIds(): ?array
    {
        return $this->matchIds;
    }

    public function getDateFrom(): ?string
    {
        return $this->dateFrom;
    }

    public function getDateTo(): ?string
    {
        return $this->dateTo;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['competitions'] ?? null,
            $data['ids'] ?? null,
            $data['dateFrom'] ?? null,
            $data['dateTo'] ?? null,
            $data['status'] ?? null
        );
    }
}
