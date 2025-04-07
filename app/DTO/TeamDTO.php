<?php

namespace App\DTO;

class TeamDTO implements \JsonSerializable
{
    private int $id;
    private string $name;
    private string $shortName;
    private string $tla;
    private string $crest;
    private ?array $headToHeadStats = null;

    public function __construct(
        int $id,
        string $name,
        string $shortName,
        string $tla,
        string $crest
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->shortName = $shortName;
        $this->tla = $tla;
        $this->crest = $crest;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function getTla(): string
    {
        return $this->tla;
    }

    public function getCrest(): string
    {
        return $this->crest;
    }

    public function setHeadToHeadStats(array $stats): void
    {
        $this->headToHeadStats = $stats;
    }

    public function getHeadToHeadStats(): ?array
    {
        return $this->headToHeadStats;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'shortName' => $this->shortName,
            'tla' => $this->tla,
            'crest' => $this->crest
        ];

        if ($this->headToHeadStats !== null) {
            $data['headToHeadStats'] = $this->headToHeadStats;
        }

        return $data;
    }
}
