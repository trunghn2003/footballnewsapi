<?php

namespace App\Mapper;

use App\DTO\CompetitionDTO;
use App\Models\Competition;
use AutoMapperPlus\AutoMapper;
use AutoMapperPlus\Configuration\AutoMapperConfig;
use Exception;

class CompetitionMapper
{
    private AutoMapper $mapper;

    public function __construct()
    {
        $config = new AutoMapperConfig();

        // Model to DTO mapping
        $config->registerMapping(Competition::class, CompetitionDTO::class)
            ->forMember('id', fn(Competition $source) => $source->id)
            ->forMember('name', fn(Competition $source) => $source->name)
            ->forMember('code', fn(Competition $source) => $source->code)
            ->forMember('type', fn(Competition $source) => $source->type)
            ->forMember('emblem', fn(Competition $source) => $source->emblem);

        // DTO to Model mapping
        $config->registerMapping(CompetitionDTO::class, Competition::class)
            ->forMember('id', fn(CompetitionDTO $source) => $source->getId())
            ->forMember('name', fn(CompetitionDTO $source) => $source->getName())
            ->forMember('code', fn(CompetitionDTO $source) => $source->getCode())
            ->forMember('type', fn(CompetitionDTO $source) => $source->getType())
            ->forMember('emblem', fn(CompetitionDTO $source) => $source->getEmblem());

        $this->mapper = new AutoMapper($config);
    }

    /**
     * Convert Competition model to CompetitionDTO
     *
     * @param Competition $competition
     * @return CompetitionDTO
     * @throws Exception
     */
    public function toDto(Competition $competition): CompetitionDTO
    {
        try {
            return $this->mapper->map($competition, CompetitionDTO::class);
        } catch (Exception $exception) {
            throw new Exception("Error mapping Competition to DTO: " . $exception->getMessage());
        }
    }

    /**
     * Convert CompetitionDTO to Competition model
     *
     * @param CompetitionDTO $competitionDTO
     * @return Competition
     * @throws Exception
     */
    public function toEntity(CompetitionDTO $competitionDTO): Competition
    {
        try {
            return $this->mapper->map($competitionDTO, Competition::class);
        } catch (Exception $exception) {
            throw new Exception("Error mapping DTO to Competition: " . $exception->getMessage());
        }
    }

    /**
     * Convert a collection of Competition models to DTOs
     *
     * @param array $competitions
     * @return array
     */
    public function toDtoCollection(array $competitions): array
    {
        return array_map(fn($competition) => $this->toDto($competition), $competitions);
    }

    /**
     * Convert a collection of DTOs to Competition models
     *
     * @param array $dtos
     * @return array
     */
    public function toEntityCollection(array $dtos): array
    {
        return array_map(fn($dto) => $this->toEntity($dto), $dtos);
    }
}
