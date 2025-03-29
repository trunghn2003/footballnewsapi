<?php

namespace App\DTO;

class TeamDTO implements \JsonSerializable
{
    private int $id;
    private string $name;
    private string $shortName;
    private string $tla;
    private string $crest;

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

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'shortName' => $this->shortName,
            'tla' => $this->tla,
            'crest' => $this->crest
        ];
    }
}