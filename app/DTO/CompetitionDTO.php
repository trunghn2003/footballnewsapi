<?php

namespace App\DTO;

class CompetitionDTO implements \JsonSerializable
{
    /**
     * @param int $id
     * @param string|null $name
     * @param string|null $code
     * @param string|null $type
     * @param string|null $emblem
     * @param AreaDTO|null $area
     * @param SeasonDTO|null $season
     */
    public function __construct(
        private int $id,
        private ?string $name,
        private ?string $code,
        private ?string $type,
        private ?string $emblem,
        private ?AreaDTO $area = null,
        private ?SeasonDTO $season = null
    ) {
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getEmblem(): ?string
    {
        return $this->emblem;
    }

    public function setEmblem(?string $emblem): void
    {
        $this->emblem = $emblem;
    }

    public function getArea(): ?AreaDTO
    {
        return $this->area;
    }

    public function setArea(?AreaDTO $area): void
    {
        $this->area = $area;
    }

    public function getSeason(): ?SeasonDTO
    {
        return $this->season;
    }

    public function setSeason(?SeasonDTO $season): void
    {
        $this->season = $season;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'emblem' => $this->emblem,
            'area' => $this->area,
            'currentSeason' => $this->season,
        ];
    }
}
