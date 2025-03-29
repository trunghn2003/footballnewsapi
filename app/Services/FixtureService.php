<?php

namespace App\Services;

use App\Mapper\LineupMapper;
use App\Repositories\FixtureRepository;
use App\Repositories\PersonRepository;
use App\DTO\FixtureDTO;
use App\Mapper\FixtureMapper;
use App\Models\Formation;
use App\Models\LineupPlayer;
use App\Repositories\LineUpPlayerRepository;
use App\Repositories\LineupRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FixtureService
{
    private FixtureRepository $fixtureRepository;
    private string $apiToken;
    private string $apiUrlFootball;
    private CompetitionService $competitionService;
    private TeamService $teamService;
    private LineupRepository $lineupRepository;
    private LineUpPlayerRepository $lineUpPlayerRepository;
    private PersonRepository $personRepository;
    private LineupMapper $lineupMapper;

    public function __construct(
        FixtureRepository $fixtureRepository,
        CompetitionService $competitionService,
        TeamService $teamService,
        LineupRepository $lineupRepository,
        LineUpPlayerRepository $lineUpPlayerRepository,
        PersonRepository $personRepository,
        LineupMapper $lineupMapper,
    ) {
        $this->fixtureRepository = $fixtureRepository;
        $this->apiToken = env('API_FOOTBALL_TOKEN');
        $this->apiUrlFootball = env('API_FOOTBALL_URL');
        $this->competitionService = $competitionService;
        $this->teamService = $teamService;
        $this->lineupRepository = $lineupRepository;
        $this->lineUpPlayerRepository = $lineUpPlayerRepository;
        $this->personRepository = $personRepository;
        $this->lineupMapper = $lineupMapper;
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

                            $homeTeamId = $data['homeTeam']['id'];
                            $awayTeamId = $data['awayTeam']['id'];
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

    private function mapPositionToGroup($position)
    {
        $map = [
            'Goalkeeper'         => 'G',
            'Left-Back'          => 'D',
            'Right-Back'         => 'D',
            'Centre-Back'        => 'D',
            'Defence'            => 'D',
            'Central Midfield'   => 'M',
            'Attacking Midfield' => 'M',
            'Defensive Midfield' => 'M',
            'Left Midfield'      => 'M',
            'Right Midfield'     => 'M',
            'Midfield'           => 'M',
            'Left Winger'        => 'F',
            'Right Winger'       => 'F',
            'Centre-Forward'     => 'F',
            'Offence'            => 'F'
        ];

        return $map[$position] ?? null;
    }

    public function createRandomLineup($fixture_id, $team_id, $players, $formation)
    {
        $formationPositions = Formation::getFormation($formation);

        $lineup = $this->lineupRepository->create([

            'fixture_id' => $fixture_id,
            'team_id'    => $team_id,
            'formation'  => $formation
        ]);

        $remainingPlayers = collect($players)->shuffle();
        $starterCount = 0;
        foreach ($formationPositions as $pos) {
            if ($starterCount >= 11) break;
            if ($remainingPlayers->isEmpty()) {
                break;
            }

            $group = $pos['group'];

            $filteredPlayers = $remainingPlayers->filter(function ($player) use ($group) {

                $playerGroup = $this->mapPositionToGroup($player->position);
                return $playerGroup === $group;
            });


            if ($filteredPlayers->isNotEmpty()) {
                $selected = $filteredPlayers->random();
            } else {
                $selected = $remainingPlayers->random();
            }

            $this->lineUpPlayerRepository->create([
                'lineup_id'    => $lineup->id,
                'player_id'    => $selected->id,
                'position'     => $pos['position'],
                'grid_position'         => $pos['grid'],
                'shirt_number' => $selected->shirt_number ?? rand(1, 99),
                'is_substitute'   => 0
            ]);
            $starterCount++;

            $remainingPlayers = $remainingPlayers->reject(function ($player) use ($selected) {
                return $player->id === $selected->id;
            });
        }
        $substituteCount = 0;
        while ($substituteCount < 7 && $remainingPlayers->isNotEmpty()) {
            $substitute = $remainingPlayers->random();


            $this->lineUpPlayerRepository->create([
                'lineup_id'     => $lineup->id,
                'player_id'     => $substitute->id,
                'position'      => null,
                'grid_position' => null,
                'shirt_number'  => $substitute->shirt_number ?? rand(1, 99),
                'is_substitute' => 1
            ]);

            $substituteCount++;


            $remainingPlayers = $remainingPlayers->reject(function ($player) use ($substitute) {
                return $player->id === $substitute->id;
            });
        }
        $totalPlayers = $starterCount + $substituteCount;
        if ($totalPlayers < 18) {
            Log::warning("Lineup {$lineup->id} created with only {$totalPlayers} players");
        } else {
            Log::info("Lineup {$lineup->id} created with {$totalPlayers} players");
        }

        return $lineup;
    }

    public function getFixtureById(int $id): ?FixtureDTO
    {
        $fixture = $this->fixtureRepository->findById($id);
        $formations = ['4-4-2', '4-3-3', '3-5-2'];

        $homeLineup = $fixture->homeLineup;
        if (!$homeLineup)
            $this->createRandomLineup($fixture->id, $fixture->home_team_id, $fixture->homeTeam->players, $formations[rand(0, count($formations) - 1)]);

        $awayLineup = $fixture->awayLineup;
        // dump($awayLineup);
        if (!$awayLineup) {
            $this->createRandomLineup($fixture->id, $fixture->away_team_id, $fixture->awayTeam->players, $formations[rand(0, count($formations) - 1)]);
        }
        if (!$fixture) {
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
            'data'  => array_map(function ($fixture) {

                $formations = ['4-4-2', '4-3-3', '3-5-2'];
                // dump($fixture->homeTeam);
                // dd($fixture->homeTeam);
                $homeLineup = $fixture->homeLineup;
                if (!$homeLineup)
                    $this->createRandomLineup($fixture->id, $fixture->home_team_id, $fixture->homeTeam->players, $formations[rand(0, count($formations) - 1)]);

                $awayLineup = $fixture->awayLineup;
                // dump($awayLineup);
                if (!$awayLineup) {
                    $this->createRandomLineup($fixture->id, $fixture->away_team_id, $fixture->awayTeam->players, $formations[rand(0, count($formations) - 1)]);
                }

                $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                $fixtureDto  = FixtureMapper::fromModel($fixture);
                $homeLineupDto =  $this->lineupMapper->toDTO($fixture->homeLineup);
                $awayLineupDto =  $this->lineupMapper->toDTO($fixture->awayLineup);
                $fixtureDto->setHomeLineup($homeLineupDto);
                $fixtureDto->setAwayLineup($awayLineupDto);
                $fixtureDto->setCompetition($competition);

                $fixtureDto->setCompetition($competition);
                return $fixtureDto;
            }, $fixtures->items()),
            'pagination' => [
                'current_page' => $fixtures->currentPage(),
                'per_page'     => $fixtures->perPage(),
                'total'        => $fixtures->total()
            ]
        ];
    }
}
