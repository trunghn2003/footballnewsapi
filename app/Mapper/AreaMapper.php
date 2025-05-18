<?php

namespace App\Mapper;

use App\DTO\AreaDTO;
use App\Models\Area;
use AutoMapperPlus\AutoMapper;
use AutoMapperPlus\Configuration\AutoMapperConfig;

class AreaMapper
{
    private AutoMapper $mapper;

    public function __construct()
    {
        $config = new AutoMapperConfig();


        $config->registerMapping(Area::class, AreaDTO::class)
            ->forMember('id', fn(Area $source) => $source->id)
            ->forMember('name', fn(Area $source) => $source->name)
            ->forMember('code', fn(Area $source) => $source->code)
            ->forMember('flag', fn(Area $source) => $source->flag);


        $config->registerMapping(AreaDTO::class, Area::class)
            ->forMember('id', fn(AreaDTO $source) => $source->getId())
            ->forMember('name', fn(AreaDTO $source) => $source->getName())
            ->forMember('code', fn(AreaDTO $source) => $source->getCode())
            ->forMember('flag', fn(AreaDTO $source) => $source->getFlag());

        $this->mapper = new AutoMapper($config);
    }

    /**
     * Convert Area model to AreaDTO
     *
     * @param Area $area
     * @return AreaDTO
     */
    public function toDTO(Area $area): AreaDTO
    {
        return $this->mapper->map($area, AreaDTO::class);
    }

    /**
     * Convert AreaDTO to Area model
     *
     * @param AreaDTO $dto
     * @return Area
     */
    public function toModel(AreaDTO $dto): Area
    {
        return $this->mapper->map($dto, Area::class);
    }

    /**
     * Convert a collection of Area models to AreaDTOs
     *
     * @param array $areas
     * @return array
     */
    public function toDTOCollection(array $areas): array
    {
        return array_map(fn($area) => $this->toDTO($area), $areas);
    }

    /**
     * Convert a collection of AreaDTOs to Area models
     *
     * @param array $dtos
     * @return array
     */
    public function toModelCollection(array $dtos): array
    {
        return array_map(fn($dto) => $this->toModel($dto), $dtos);
    }
}
