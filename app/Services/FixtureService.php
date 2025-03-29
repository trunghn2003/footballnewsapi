<?php

namespace App\Services;

use App\Repositories\FixtureRepository;
use App\Repositories\PersonRepository;
use App\DTO\FixtureDTO;
use App\Mapper\FixtureMapper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FixtureService
{
    private FixtureRepository $fixtureRepository;
    private string $apiToken;
    private string $apiUrlFootball;
    private CompetitionService $competitionService;

    public function __construct(FixtureRepository $fixtureRepository, CompetitionService $competitionService    )
    {
        $this->fixtureRepository = $fixtureRepository;
        $this->apiToken = env('API_FOOTBALL_TOKEN');
        $this->apiUrlFootball = env('API_FOOTBALL_URL');
        $this->competitionService = $competitionService;
    }

    public function syncFixtures()
    {
        try {
            $names = [
                'PL',
                'CL',
                'FL1',
                'BL1',
                'SA',
                'PD',
            ];
            foreach ($names as $name) {
                $response = Http::withHeaders([
                    'X-Auth-Token' => $this->apiToken
                ])->get("{$this->apiUrlFootball}/competitions/{$name}/matches");

                if (!$response->successful()) {
                    throw new \Exception("API request failed: {$response->status()}");
                }

                $datas = $response->json()['matches'];

                DB::beginTransaction();

                if (isset($datas) && is_array($datas)) {
                    foreach ($datas as $data) {
                        if (isset($data['homeTeam']) && isset($data['awayTeam'])) {
                            $this->fixtureRepository->createOrUpdate($data);
                        }
                    }
                }

                DB::commit();
            }

            return [
                'success' => true
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

    public function getFixtureById(int $id): ?FixtureDTO
    {
        $fixture = $this->fixtureRepository->findById($id);

        if (!$fixture) {
            dd(1);
            return null;
        }

        $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
        $fixtureDto = FixtureMapper::fromModel($fixture);
        $fixtureDto->setCompetition($competition);
        return $fixtureDto;
    }

    public function getFixtures(array $filters = [], int $perPage = 10, int $page = 1): array
    {
        $fixtures = $this->fixtureRepository->getFixtures($filters, $perPage, $page);

        return [
            'data' => array_map(function ($fixture) {
                $competition = $this->competitionService->getCompetitionById($fixture->competition_id); 
                $fixtureDto = FixtureMapper::fromModel($fixture);
                $fixtureDto->setCompetition($competition);
                return $fixtureDto;
            }, $fixtures->items()),
            'pagination' => [
                'current_page' => $fixtures->currentPage(),
                'per_page' => $fixtures->perPage(),
                'total' => $fixtures->total()
            ]
        ];
    }
}