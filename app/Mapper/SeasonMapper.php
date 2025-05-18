<?php

namespace App\Mapper;

use App\DTO\SeasonDTO;
use App\Models\Season;
use AutoMapperPlus\AutoMapper;
use AutoMapperPlus\Configuration\AutoMapperConfig;
use Carbon\Carbon;
use Illuminate\Support\Carbon as IlluminateCarbon;
use Exception;

class SeasonMapper
{
    private AutoMapper $mapper;

    public function __construct()
    {
        $config = new AutoMapperConfig();

        // Model to DTO mapping
        $config->registerMapping(Season::class, SeasonDTO::class)
            ->forMember('id', fn(Season $source) => $source->id)
            ->forMember('name', fn(Season $source) => $source->name)
            ->forMember('start', function (Season $source) {
                // Convert Carbon to string format
                return $source->start_date?->format('Y-m-d') ;
            })
            ->forMember('end', function (Season $source) {
                return $source->end_date?->format('Y-m-d');
            });

        // DTO to Model mapping
        $config->registerMapping(SeasonDTO::class, Season::class)
            ->forMember('id', fn(SeasonDTO $source) => $source->getId())
            ->forMember('name', fn(SeasonDTO $source) => $source->getName())
            ->forMember('start_date', function (SeasonDTO $source) {
                // Convert string back to Carbon
                return $source->getStart() ? IlluminateCarbon::parse($source->getStart()) : null ;
            })
            ->forMember('end_date', function (SeasonDTO $source) {
                return $source->getEnd() ? IlluminateCarbon::parse($source->getEnd()) : null;
            });

        $this->mapper = new AutoMapper($config);
    }

    /**
     * Convert Season model to SeasonDTO
     */
    public function toDTO(Season $season): SeasonDTO
    {
        try {
            return $this->mapper->map($season, SeasonDTO::class);
        } catch (Exception $exception) {
            throw new Exception("Error mapping Season to DTO: " . $exception->getMessage());
        }
    }

    /**
     * Convert SeasonDTO to Season model
     */
    public function toModel(SeasonDTO $dto): Season
    {
        try {
            return $this->mapper->map($dto, Season::class);
        } catch (Exception $exception) {
            throw new Exception("Error mapping DTO to Season: " . $exception->getMessage());
        }
    }
}
