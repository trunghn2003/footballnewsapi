<?php

namespace App\DTO;

class SeasonDTO implements \JsonSerializable
{
    private int $id;
    private ?string $name;
    private ?string $start;
    private ?string $end;
    private ?string $competitionName = null;
/**
     * @param int $id
     * @param String|null $name
     * @param String|null $start
     * @param String|null $end
     * @param string|null $competitionName
     */
    public function __construct(int $id, ?string $name, ?string $start, ?string $end, ?string $competitionName)
    {
        $this->id = $id;
        $this->name = $name;
        $this->start = $start;
        $this->end = $end;
        $this->competitionName = $competitionName;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getStart(): ?string
    {
        return $this->start;
    }

    public function setStart(?string $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): ?string
    {
        return $this->end;
    }

    public function setEnd(?string $end): void
    {
        $this->end = $end;
    }

    public function getCompetitionName(): ?string
    {
        return $this->competitionName;
    }

    public function setCompetitionName(?string $competitionName): void
    {
        $this->competitionName = $competitionName;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start' => $this->start,
            'end' => $this->end,
            'competitionName' => $this->getCompetitionName()
        ];
    }
}
