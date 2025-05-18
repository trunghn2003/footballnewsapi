<?php

namespace App\Services;

use App\DTO\CompetitionDTO;
use App\Mapper\AreaMapper;
use App\Mapper\CompetitionMapper;
use App\Mapper\SeasonMapper;
use App\Repositories\AreaRepository;
use App\Repositories\CompetitionRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Competition;

class CompetitionService
{
    private string $apiUrlFootball;
    private string $apiToken;

    public function __construct(
        private CompetitionRepository $competitionRepository,
        private AreaRepository $areaRepository,
        private SeasonMapper $seasonMapper,
        private AreaMapper $areaMapper,
        private CompetitionMapper $competitionMapper
    ) {
        $this->apiUrlFootball = config('services.football_api.url');
        $this->apiToken = config('services.football_api.token');
    }

    /**
     * Sync all competitions from the API
     */
    public function syncCompetitions(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken
            ])->get("{$this->apiUrlFootball}/competitions");

            if (!$response->successful()) {
                throw new \Exception("API request failed: {$response->status()}");
            }

            $competitions = $response->json()['competitions'];

            $stats = [
                'created' => 0,
                'updated' => 0,
                'failed' => 0
            ];
            DB::beginTransaction();
            foreach ($competitions as $competitionData) {
                try {
                    $area = $this->areaRepository->findById($competitionData['area']['id']);

                    if (!$area) {
                        throw new \Exception("Area not found for competition ID {$competitionData['id']}");
                    }

                    $competition = $this->competitionRepository->createOrUpdate([
                        'id' => $competitionData['id'],
                        'name' => $competitionData['name'],
                        'code' => $competitionData['code'] ?? null,
                        'type' => $competitionData['type'],
                        'emblem' => $competitionData['emblem'] ?? null,
                        'area_id' => $area->id,
                        'lastUpdated' => $competitionData['lastUpdated'] ?? now(),
                    ]);

                    $wasRecentlyCreated = $competition->wasRecentlyCreated;
                    $stats[$wasRecentlyCreated ? 'created' : 'updated']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                    Log::error("Failed to sync competition ID {$competitionData['id']}: {$e->getMessage()}");
                }
            }
            DB::commit();

            return [
                'success' => true,
                'stats' => $stats
            ];
        } catch (\Exception $e) {
            Log::error("Competition sync failed: {$e->getMessage()}");
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get competition details by ID
     */
    public function getCompetitionDetails(int $competitionId): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken
            ])->get("{$this->apiUrlFootball}/competitions/{$competitionId}");

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch competition details");
            }

            return $response->json();
        } catch (\Exception $e) {
            throw ModelNotFoundException($e->getMessage());
            Log::error("Failed to get competition details: {$e->getMessage()}");
            return null;
        }
    }

    public function getCompetitions($filters, $perPage, $page)
    {
        try {
            $competitions = $this->competitionRepository->getAll($filters, $perPage, $page);
            $result = [];
            foreach ($competitions as $competition) {
                $area =  $competition->area;
                $areaDTO = $this->areaMapper->toDTO($area);
                $season = $competition->currentSeason ?? null;
                $seaDTO = $season ? $this->seasonMapper->toDTO($season) : null;
                if (isset($seaDTO)) {
                    $seaDTO->setCompetitionName($competition->name);
                }
                //                 dump($seaDTO);
                $competitionDto = $this->competitionMapper->toDTO($competition);
                $competitionDto->setArea($areaDTO);
                $competitionDto->setSeason($seaDTO);

                $result[] = $competitionDto;
            }
            return [
                'total' => $competitions->total(),
                'current_page' => $competitions->currentPage(),
                'per_page' => $competitions->perPage(),
                'competitions' => $result
            ];
            return $result;
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            throw ($e);
        }
    }

    public function getCompetitionById($id)
    {
        try {
            $competition = $this->competitionRepository->getById($id);


            $area =  $competition->area;
            $areaDTO = $this->areaMapper->toDTO($area);
            $season = $competition->currentSeason ?? null;
            $seaDTO = $season ? $this->seasonMapper->toDTO($season) : null;
            if (isset($seaDTO)) {
                $seaDTO->setCompetitionName($competition->name);
            }
            $competitionDto = $this->competitionMapper->toDTO($competition);
            $competitionDto->setArea($areaDTO);
            $competitionDto->setSeason($seaDTO);
            return $competitionDto;
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            throw ($e);
        }
    }

    /**
     * Get featured competitions
     */
    public function getFeaturedCompetitions()
    {
        try {
            $competitions = $this->competitionRepository->getFeatured();
            $result = [];

            foreach ($competitions as $competition) {
                $area = $competition->area;
                $areaDTO = $this->areaMapper->toDTO($area);
                $season = $competition->currentSeason ?? null;
                $seaDTO = $season ? $this->seasonMapper->toDTO($season) : null;
                if (isset($seaDTO)) {
                    $seaDTO->setCompetitionName($competition->name);
                }

                $competitionDto = $this->competitionMapper->toDTO($competition);
                $competitionDto->setArea($areaDTO);
                $competitionDto->setSeason($seaDTO);

                $result[] = $competitionDto;
            }

            return [
                'competitions' => $result,
                'total' => count($result)
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw ($e);
        }
    }
    public function addToFavourite($id)
    {
        try {
            $competition = $this->competitionRepository->getById($id);
            if (!$competition) {
                throw new \Exception("Competition not found");
            }
            $user = auth()->user();
            $favouriteCompetitions = $user->favourite_competitions ?? [];
            if (!is_array($favouriteCompetitions)) {
                $favouriteCompetitions = json_decode($favouriteCompetitions, true) ?? [];
            }
            // dd($favouriteCompetitions);
            if (!in_array($competition->id, $favouriteCompetitions)) {
                $favouriteCompetitions[] = $competition->id;
                $user->favourite_competitions = $favouriteCompetitions;
                $user->save();
            }
            return [
                'success' => true,
                'message' => 'Competition added to favourites'
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw ($e);
        }
    }
    public function getFavouriteCompetitions()
    {
        try {
            $user = auth()->user();
            $favouriteCompetitions = $user->favourite_competitions ?? [];
            if (!is_array($favouriteCompetitions)) {
                $favouriteCompetitions = json_decode($favouriteCompetitions, true) ?? [];
            }

            // dd($favouriteCompetitions);
            $competitions = $this->competitionRepository->getByIds($favouriteCompetitions);
            $result = [];
            foreach ($competitions as $competition) {
                $area = $competition->area;
                $areaDTO = $this->areaMapper->toDTO($area);
                $season = $competition->currentSeason ?? null;
                $seaDTO = $season ? $this->seasonMapper->toDTO($season) : null;
                if (isset($seaDTO)) {
                    $seaDTO->setCompetitionName($competition->name);
                }

                $competitionDto = $this->competitionMapper->toDTO($competition);
                $competitionDto->setArea($areaDTO);
                $competitionDto->setSeason($seaDTO);

                $result[] = $competitionDto;
            }
            return [
                'competitions' => $result,
                'total' => count($result)
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw ($e);
        }
    }

    public function removeFromFavourite($id)
    {
        try {
            $user = auth()->user();
            $favouriteCompetitions = $user->favourite_competitions ?? [];
            if (!is_array($favouriteCompetitions)) {
                $favouriteCompetitions = json_decode($favouriteCompetitions, true) ?? [];
            }
            if (($key = array_search($id, $favouriteCompetitions)) !== false) {
                unset($favouriteCompetitions[$key]);
                $user->favourite_competitions = array_values($favouriteCompetitions);
                $user->save();
            }
            return [
                'success' => true,
                'message' => 'Competition removed from favourites'
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw ($e);
        }
    }

    /**
     * Find a competition by name or code
     *
     * @param string $name
     * @return Competition|null
     */
    public function findCompetitionByName(string $name)
    {
        return $this->competitionRepository->findByName($name);
    }
}
