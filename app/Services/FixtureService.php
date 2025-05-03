<?php

namespace App\Services;

use App\Mapper\LineupMapper;
use App\Repositories\FixtureRepository;
use App\Repositories\PersonRepository;
use App\DTO\FixtureDTO;
use App\Mapper\FixtureMapper;
use App\Mapper\TeamMapper;
use App\Models\Fixture;
use App\Models\Formation;
use App\Models\LineupPlayer;
use App\Models\User;
use App\Repositories\LineUpPlayerRepository;
use App\Repositories\LineUpRepository;
use App\Repositories\SeasonRepository;
use App\Traits\PushNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\FixtureStatistic;

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
    private SeasonRepository $seasonRepository;
    private LineUpRepository $lineUpRepository;
    use PushNotification;

    public function __construct(
        FixtureRepository      $fixtureRepository,
        CompetitionService     $competitionService,
        TeamService            $teamService,
        LineupRepository       $lineupRepository,
        LineUpPlayerRepository $lineUpPlayerRepository,
        PersonRepository       $personRepository,
        LineupMapper           $lineupMapper,
        SeasonRepository       $seasonRepository,
        LineUpRepository       $lineUpRepository
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
        $this->seasonRepository = $seasonRepository;
        $this->lineUpRepository = $lineUpRepository;
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
                // dd($datas);

                DB::beginTransaction();

                if (isset($datas) && is_array($datas)) {
                    foreach ($datas as $data) {
                        if (isset($data['homeTeam']) && isset($data['awayTeam'])) {
                            $fixture = $this->fixtureRepository->createOrUpdate($data);
                            // dd($fixture);
                            if ($fixture->wasRecentlyCreated) {
                            } else if ($fixture->wasChanged()) {
                                // Check if score has changed
                                if ($fixture->wasChanged(['status'])  && $fixture->status == 'FINISHED') {
                                    $this->sendMatchScoreNotification($fixture);
                                }
                            }
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
            'Goalkeeper' => 'G',
            'Left-Back' => 'D',
            'Right-Back' => 'D',
            'Centre-Back' => 'D',
            'Defence' => 'D',
            'Central Midfield' => 'M',
            'Attacking Midfield' => 'M',
            'Defensive Midfield' => 'M',
            'Left Midfield' => 'M',
            'Right Midfield' => 'M',
            'Midfield' => 'M',
            'Left Winger' => 'F',
            'Right Winger' => 'F',
            'Centre-Forward' => 'F',
            'Offence' => 'F'
        ];

        return $map[$position] ?? null;
    }

    public function createRandomLineup($fixture_id, $team_id, $players, $formation)
    {
        $formationPositions = Formation::getFormation($formation);

        $lineup = $this->lineupRepository->create([

            'fixture_id' => $fixture_id,
            'team_id' => $team_id,
            'formation' => $formation
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
                'lineup_id' => $lineup->id,
                'player_id' => $selected->id,
                'position' => $pos['position'],
                'grid_position' => $pos['grid'],
                'shirt_number' => $selected->shirt_number ?? rand(1, 99),
                'is_substitute' => 0
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
                'lineup_id' => $lineup->id,
                'player_id' => $substitute->id,
                'position' => null,
                'grid_position' => null,
                'shirt_number' => $substitute->shirt_number ?? rand(1, 99),
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

    /**
     * Get fixture by ID with lineup information
     *
     * @param int $id
     * @return array
     */
    public function getFixtureById(int $id): array
    {
        // try {
        $fixture = $this->fixtureRepository->findById($id);
        if (!$fixture) {
            return [
                'success' => false,
                'message' => 'Fixture not found'
            ];
        }

        // Get statistics
        $statistics = $this->getFixtureStatistics($fixture->id);

        // Map fixture to DTO
        $fixtureDto = FixtureMapper::fromModel($fixture);

        // Get competition and teams info
        $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
        $fixtureDto->setCompetition($competition);

        // Get home team information
        $homeTeam = $fixture->homeTeam;
        if (isset($homeTeam)) {
            $fixtureDto->setHomeTeam(TeamMapper::fromModel($homeTeam));
        }

        // Get away team information
        $awayTeam = $fixture->awayTeam;
        if (isset($awayTeam)) {
            $fixtureDto->setAwayTeam(TeamMapper::fromModel($awayTeam));
        }

        // Check if lineups exist, if not fetch from API


        return [
            'success' => true,
            'fixture' => $fixtureDto,
            'statistics' => $statistics,
        ];
        // } catch (\Exception $e) {
        //     Log::error("Error getting fixture by ID {$id}: " . $e->getMessage());
        //     return [
        //         'success' => false,
        //         'message' => $e->getMessage()
        //     ];
        // }
    }

    public function mapLineupToArray($lineup)
    {
        if (!$lineup) {
            return null;
        }
        return [

            'formation' => $lineup->formation,
            'startXI' => $lineup->lineupPlayers
                ->filter(function ($player) {
                    return $player->is_substitute == 0;
                })
                ->sortBy(function ($player) {
                    list($row, $col) = explode(':', $player->grid_position);
                    return $row * 100 + $col;
                })->map(function ($player) {
                    return [
                        'id' => $player->player_id,
                        'position' => $player->position,
                        'name' => $player->player->name,
                        'shirt_number' => $player->shirt_number,
                        'is_substitute' => $player->is_substitute,
                        'grid' => $player->grid_position,
                        'statistics' => $player->statistics,
                    ];
                }),
            'sub' => $lineup->lineupPlayers->filter(function ($player) {
                return $player->is_substitute == 1;
            })->map(function ($player) {
                return [
                    'id' => $player->player_id,
                    'position' => $player->position,
                    'name' => $player->player->name,
                    'shirt_number' => $player->shirt_number,
                    'is_substitute' => $player->is_substitute,
                    'statistics' => $player->statistics,
                ];
            }),
        ];
    }

    public function getFixtures(array $filters = [], int $perPage = 10, int $page = 1): array
    {
        $filters['recently'] = 1;
        $fixtures = $this->fixtureRepository->getFixtures($filters, $perPage, $page, 1);
        if (isset($fixtures) && count($fixtures) > 0)
            // dd($fixtures->items());
            return [
                'fixtures' => array_map(function ($fixture) {
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto = FixtureMapper::fromModel($fixture);
                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $fixtureDto->setHomeTeam((TeamMapper::fromModel($homeTeam)));
                    }
                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $fixtureDto->setAwayTeam((TeamMapper::fromModel($awayTeam)));
                    }
                    $fixtureDto->setCompetition($competition);
                    return $fixtureDto;
                }, $fixtures->items()),
                'pagination' => [
                    'current_page' => $fixtures->currentPage(),
                    'per_page' => $fixtures->perPage(),
                    'total' => $fixtures->total()
                ]
            ];
        else return [
            'fixtures' => [],
            'pagination' => [
                'current_page' => 0,
                'per_page' => 0,
                'total' => 0
            ]
        ];
    }

    public function getFixtureByCompetition($filters)
    {
        $fixtures = $this->fixtureRepository->getFixtures($filters, 50, 1, $flag = true);
        if (isset($fixtures) && count($fixtures) > 0)

            return [
                'fixtures' => array_map(function ($fixture) {
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto = FixtureMapper::fromModel($fixture);
                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $fixtureDto->setHomeTeam((TeamMapper::fromModel($homeTeam)));
                    }
                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $fixtureDto->setAwayTeam((TeamMapper::fromModel($awayTeam)));
                    }
                    $fixtureDto->setCompetition($competition);
                    return $fixtureDto;
                }, $fixtures->items()),
                'pagination' => [
                    'current_page' => $fixtures->currentPage(),
                    'per_page' => $fixtures->perPage(),
                    'total' => $fixtures->total()
                ]
            ];
        return [
            'fixtures' => [],
            'pagination' => [
                'current_page' => 0,
                'per_page' => 0,
                'total' => 0
            ]
        ];
    }

    public function getRecentFixturesByTeam(int $teamId, int $limit = 5): array
    {
        // dd(1);
        $fixtures = $this->fixtureRepository->getFixturesRecent([
            'teamId' => $teamId,
            'status' => 'FINISHED'
        ], $limit, 1);

        if (!empty($fixtures->items())) {
            return [
                'fixtures' => array_map(function ($fixture) {
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto = FixtureMapper::fromModel($fixture);
                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $fixtureDto->setHomeTeam((TeamMapper::fromModel($homeTeam)));
                    }
                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $fixtureDto->setAwayTeam((TeamMapper::fromModel($awayTeam)));
                    }
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

        return [
            'fixtures' => [],
            'pagination' => [
                'current_page' => 0,
                'per_page' => 0,
                'total' => 0
            ]
        ];
    }

    public function getUpcomingFixturesByTeam(int $teamId, $filter): array
    {
        // dd($teamId);
        $fixtures = $this->fixtureRepository->getFixtures([
            'teamId' => $teamId,
            'status' => 'SCHEDULED',
            'competition' => $filter['competition'] ?? null,
            'dateFrom' => $filter['dateFrom'] ?? null,
            'dateTo' => $filter['dateTo'] ?? null,
            'teamName' => $filter['teamName'] ?? null,
            'competition_id' => $filter['competition_id'] ?? null
        ], $filter['limit'] ?? 5 , 1);

        if (isset($fixtures) && count($fixtures) > 0) {
            return [
                'fixtures' => array_map(function ($fixture) {
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto = FixtureMapper::fromModel($fixture);
                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $fixtureDto->setHomeTeam((TeamMapper::fromModel($homeTeam)));
                    }
                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $fixtureDto->setAwayTeam((TeamMapper::fromModel($awayTeam)));
                    }
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

        return [
            'fixtures' => [],
            'pagination' => [
                'current_page' => 0,
                'per_page' => 0,
                'total' => 0
            ]
        ];
    }

    /**
     * Get recent fixtures filtered by team name and/or competition name
     *
     * @param array $filters
     * @return array
     */
    public function getRecentFixturesByFilters(array $filters): array
    {
        $queryFilters = [
            'status' => 'FINISHED'
        ];

        // Add team name filter if provided
        if (!empty($filters['teamName'])) {
            $queryFilters['teamName'] = $filters['teamName'];
        }

        // Add competition filter by name if provided
        if (!empty($filters['competitionName'])) {
            $competition = $this->competitionService->findCompetitionByName($filters['competitionName']);
            if ($competition) {
                $queryFilters['competition_id'] = $competition->id;
            }
        }

        // Get fixtures with applied filters
        $limit = $filters['limit'] ?? 10;
        $page = $filters['page'] ?? 1;

        $fixtures = $this->fixtureRepository->getFixturesRecent($queryFilters, $limit, $page);

        if (!empty($fixtures->items())) {
            return [
                'fixtures' => array_map(function ($fixture) {
                    $fixtureDto = FixtureMapper::fromModel($fixture);
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto->setCompetition($competition);

                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $fixtureDto->setHomeTeam(TeamMapper::fromModel($homeTeam));
                    }

                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $fixtureDto->setAwayTeam(TeamMapper::fromModel($awayTeam));
                    }

                    return $fixtureDto;
                }, $fixtures->items()),
                'pagination' => [
                    'current_page' => $fixtures->currentPage(),
                    'per_page' => $fixtures->perPage(),
                    'total' => $fixtures->total()
                ]
            ];
        }

        return [
            'fixtures' => [],
            'pagination' => [
                'current_page' => 0,
                'per_page' => 0,
                'total' => 0
            ]
        ];
    }

    /**
     * Get upcoming fixtures regardless of team
     *
     * @param array $filters Additional filters to apply (teamName, competition_id, dateFrom, dateTo)
     * @param int $perPage Number of fixtures per page
     * @param int $page Current page number
     * @return array Upcoming fixtures with pagination data
     */
    public function getUpcomingFixtures(array $filters = [], int $perPage = 10, int $page = 1): array
    {
        // Set required filters for upcoming fixtures
        $queryFilters = [
            'status' => 'SCHEDULED',
            'dateFrom' => now()->toDateString()
        ];

        // Merge additional filters provided by the caller
        if (!empty($filters['teamName'])) {
            $queryFilters['teamName'] = $filters['teamName'];
        }

        if (!empty($filters['competitionName'])) {
            $competition = $this->competitionService->findCompetitionByName($filters['competitionName']);
            if ($competition) {
                $queryFilters['competition_id'] = $competition->id;
            }
        } else if (!empty($filters['competition_id'])) {
            $queryFilters['competition_id'] = $filters['competition_id'];
        }

        if (!empty($filters['dateTo'])) {
            $queryFilters['dateTo'] = $filters['dateTo'];
        } else if (!empty($filters['daysAhead']) && is_numeric($filters['daysAhead'])) {
            $queryFilters['dateTo'] = now()->addDays($filters['daysAhead'])->toDateString();
        }

        // Get fixtures with applied filters
        $fixtures = $this->fixtureRepository->getFixtures($queryFilters, $perPage, $page);

        if (!empty($fixtures->items())) {
            return [
                'fixtures' => array_map(function ($fixture) {
                    $fixtureDto = FixtureMapper::fromModel($fixture);
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto->setCompetition($competition);

                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $fixtureDto->setHomeTeam(TeamMapper::fromModel($homeTeam));
                    }

                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $fixtureDto->setAwayTeam(TeamMapper::fromModel($awayTeam));
                    }

                    return $fixtureDto;
                }, $fixtures->items()),
                'pagination' => [
                    'current_page' => $fixtures->currentPage(),
                    'per_page' => $fixtures->perPage(),
                    'total' => $fixtures->total()
                ]
            ];
        }

        return [
            'fixtures' => [],
            'pagination' => [
                'current_page' => 0,
                'per_page' => 0,
                'total' => 0
            ]
        ];
    }

    protected function getUsersToNotify(Fixture $match)
    {
        return User::whereJsonContains('favourite_teams', $match->homeTeam->id)
            ->orWhereJsonContains('favourite_teams', $match->awayTeam->id)
            ->get();
    }

    /**
     * Send match score notification to users
     *
     * @param Fixture $fixture
     * @return void
     */
    protected function sendMatchScoreNotification(Fixture $fixture)
    {
        // Get users who have this match's teams in their favorites
        $users = $this->getUsersToNotify($fixture);

        if ($users->isEmpty()) {
            return;
        }

        $homeTeam = $fixture->homeTeam;
        $awayTeam = $fixture->awayTeam;

        if (!$homeTeam || !$awayTeam) {
            return;
        }

        $homeScore = $fixture->full_time_home_score ?? 0;
        $awayScore = $fixture->full_time_away_score ?? 0;

        $title = "Kết quả trận đấu của " . $homeTeam->name . " và " . $awayTeam->name;
        $body = "{$homeTeam->name} {$homeScore} - {$awayScore} {$awayTeam->name}";

        foreach ($users as $user) {
            if (empty($user->fcm_token)) {
                continue;
            }

            $this->sendNotification(
                $user->fcm_token,
                $title,
                $body,
                [
                    'user_id' => $user->id,
                    'type' => 'match_score',
                    'fixture_id' => $fixture->id,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'home_team_name' => $homeTeam->name,
                    'away_team_name' => $awayTeam->name,
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                    'competition_id' => $fixture->competition_id,
                    'competition_name' => $fixture->competition->name ?? 'Unknown Competition',
                    'screen' => "FixtureDetail/?id=" . $fixture->id,

                ]
            );
        }

        Log::info("Match score notification sent for fixture ID: {$fixture->id}");
    }

    /**
     * Lấy lịch sử đối đầu giữa hai đội bóng dựa trên ID trận đấu
     *
     * @param int $fixtureId ID của trận đấu
     * @param int $limit Số lượng trận đấu muốn lấy
     * @return array
     */
    public function getHeadToHeadFixturesByFixtureId(int $fixtureId, int $limit = 10): array
    {
        $result = $this->fixtureRepository->getHeadToHeadFixturesByFixtureId($fixtureId, $limit);
        $fixtures = $result['fixtures'];
        $stats = $result['stats'];

        if ($fixtures->count() > 0) {
            return [
                'fixtures' => array_map(function ($fixture) use ($stats) {
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto = FixtureMapper::fromModel($fixture);

                    // Lấy thông tin đội chủ nhà
                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $homeTeamDto = TeamMapper::fromModel($homeTeam);

                        // Thêm thống kê đối đầu cho đội chủ nhà
                        $homeTeamId = $homeTeam->id;
                        $homeTeamStats = $homeTeamId == $stats['team1']['id'] ? $stats['team1'] : $stats['team2'];
                        $homeTeamDto->setHeadToHeadStats($homeTeamStats);

                        $fixtureDto->setHomeTeam($homeTeamDto);
                    }

                    // Lấy thông tin đội khách
                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $awayTeamDto = TeamMapper::fromModel($awayTeam);

                        // Thêm thống kê đối đầu cho đội khách
                        $awayTeamId = $awayTeam->id;
                        $awayTeamStats = $awayTeamId == $stats['team1']['id'] ? $stats['team1'] : $stats['team2'];
                        $awayTeamDto->setHeadToHeadStats($awayTeamStats);

                        $fixtureDto->setAwayTeam($awayTeamDto);
                    }

                    $fixtureDto->setCompetition($competition);
                    return $fixtureDto;
                }, $fixtures->items()),
                'stats' => $stats,
                'pagination' => [
                    'current_page' => $fixtures->currentPage(),
                    'per_page' => $fixtures->perPage(),
                    'total' => $fixtures->total()
                ]
            ];
        }

        return [
            'fixtures' => [],
            'stats' => $stats,
            'pagination' => [
                'current_page' => 0,
                'per_page' => 0,
                'total' => 0
            ]
        ];
    }

    public function syncFixturesv2()
    {
        set_time_limit(3000000);
        try {
            $names = [
                2 => 2001,
                39 => 2021,
                140 => 2014,
                145 => 2019,
                41 => 2015,
                78 => 2002
            ];
            $years = [2022, 2023, 2021];
            foreach ($names as $name => $id) {

                foreach ($years as $year) {


                    $response = Http::withHeaders([
                        'x-rapidapi-host' => "v3.football.api-sports.io",
                        "x-rapidapi-key" => "594e036ead58fc9a6ccf22f6ac50cd5f"
                    ])->get("https://v3.football.api-sports.io/fixtures?league={$name}&season={$year}");
                    // dd($response->json());
                    if (!$response->successful()) {
                        throw new \Exception("API request failed: {$response->status()}");
                    }

                    $datas = $response->json()['response'];
                    // dd($response->json());

                    DB::beginTransaction();

                    if (isset($datas) && is_array($datas)) {
                        $competition = $this->competitionService->getCompetitionById($id);
                        $season = $this->seasonRepository->getByCompetitionAndYear($id, $year);

                        //                        dd($season);
                        foreach ($datas as $data) {
                            //                            dd($data);
                            $fixture = $this->fixtureRepository->createOrUpdatev2($data, $season->id, $id);
                        }
                    }

                    DB::commit();
                }
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

    public function fetchFixturev3()
    {
        set_time_limit(3000000);
        $restructured = [
            'EPL' => ['id' => 61627, 'season' => 17, 'competition_id' => 2021],
            'Champions League' => ['id' => 61644, 'season' => 7, 'competition_id' => 2001],
            'La Liga' => ['id' => 61643, 'season' => 8, 'competition_id' => 2014],
            'Serie A' => ['id' => 63515, 'season' => 23, 'competition_id' => 2019],
            'Bundesliga' => ['id' => 63516, 'season' => 35, 'competition_id' => 2002],
            'Ligue 1' => ['id' => 61736, 'season' => 34, 'competition_id' => 2015]
        ];
        foreach ($restructured as $name => $data) {

            $response = Http::withHeaders([
                'x-rapidapi-host' => "sofascore.p.rapidapi.com",
                "x-rapidapi-key" => "3ffcbe8639mshed1c7dc03a94db6p16d136jsn775d46322204"
            ])->get("https://sofascore.p.rapidapi.com/tournaments/get-last-matches?tournamentId={$data['season']}&seasonId={$data['id']}&pageIndex=0");
            Log::info("Response: {$response->status()} - Name: {$name} - Season ID: {$data['season']} - Competition ID: {$data['id']}");
            $competition_id = $data['competition_id'];
            // dd($response->json());
            if (!$response->successful()) {
                throw new \Exception("API request failed: {$response->status()}");
            }

            $datas = $response->json('events');
            // dd($response->json());

            DB::beginTransaction();

            if (isset($datas) && is_array($datas)) {
                foreach ($datas as $d) {
                    // dd($d);
                    // $d = $d['events'];
                    $tla_home = $d['homeTeam']['nameCode'];
                    $tla_away = $d['awayTeam']['nameCode'];
                    $name_home = $d['homeTeam']['name'];
                    $name_away = $d['awayTeam']['name'];
                    $fixture = $this->fixtureRepository->findByTLAOrName($tla_home, $tla_away, $name_home, $name_away,  $competition_id);
                    if (isset($fixture)) {
                        Log::info("Fixture ID: {$fixture->id} - TLA Home: {$tla_home} - TLA Away: {$tla_away} - ID Fixture: {$d['id']}");

                        $id_fixture = $d['id'];
                        $fixture->id_fixture = $id_fixture;
                        $fixture->save();
                    }
                    // Log::info("Fixture ID: {$fixture->id} - TLA Home: {$tla_home} - TLA Away: {$tla_away} - ID Fixture: {$id_fixture}");
                    // }

                }
            }
            sleep(5);

            DB::commit();
        }
        return [
            'success' => true
        ];
    }

    /**
     * Save fixture statistics if they don't exist
     *
     * @param int $fixtureId
     * @param array $statistics
     * @return void
     */
    public function saveFixtureStatistics(int $fixtureId, array $statistics): void
    {
        // Check if statistics already exist
        $existingStats = FixtureStatistic::where('fixture_id', $fixtureId)->count();
        if ($existingStats > 0) {
            return;
        }

        $statisticsToSave = [];

        // Process each period (ALL, 1ST, 2ND)
        foreach ($statistics as $periodData) {
            $period = $periodData['period'] ?? 'ALL';

            // Process each group in the period
            foreach ($periodData['groups'] as $group) {
                $groupName = $group['groupName'] ?? 'General';

                // Process each statistic item in the group
                foreach ($group['statisticsItems'] as $item) {
                    $statisticsToSave[] = [
                        'fixture_id' => $fixtureId,
                        'period' => $period,
                        'group_name' => $groupName,
                        'statistic_name' => $item['name'] ?? '',
                        'key' => $item['key'] ?? '',
                        'home' => $item['home'] ?? '0',
                        'away' => $item['away'] ?? '0',
                        'compare_code' => $item['compareCode'] ?? 0,
                        'statistics_type' => $item['statisticsType'] ?? '',
                        'value_type' => $item['valueType'] ?? '',
                        'home_value' => $item['homeValue'] ?? 0,
                        'away_value' => $item['awayValue'] ?? 0,
                        'home_total' => $item['homeTotal'] ?? null,
                        'away_total' => $item['awayTotal'] ?? null,
                        'render_type' => $item['renderType'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (!empty($statisticsToSave)) {
            try {
                FixtureStatistic::insert($statisticsToSave);
            } catch (\Exception $e) {
                Log::error("Error saving fixture statistics: " . $e->getMessage());
                Log::error("Statistics data: " . json_encode($statisticsToSave));
            }
        }
    }

    /**
     * Get fixture statistics from API if they don't exist in database
     *
     * @param int $fixtureId
     * @return array|null
     */
    public function getFixtureStatistics(int $fixtureId): ?array
    {
        // Check if statistics exist in database
        $fixture = $this->fixtureRepository->findById($fixtureId);
        if (!$fixture) {
            Log::error("Fixture not found for ID: {$fixtureId}");
            return null;
        }
        if (!$fixture->id_fixture) {
            Log::error("Fixture ID is missing for fixture ID: {$fixtureId}");
            return null;
        }
        if ($fixture->status != 'FINISHED') {
            Log::info("Fixture ID: {$fixtureId} is not finished. Status: {$fixture->status}");
            return null;
        }
        $existingStats = FixtureStatistic::where('fixture_id', $fixtureId)->get();
        if ($existingStats->isNotEmpty()) {
            // Transform database data to match API structure
            $statistics = [];
            foreach ($existingStats as $stat) {
                $period = $stat->period;
                if (!isset($statistics[$period])) {
                    $statistics[$period] = [
                        'period' => $period,
                        'groups' => []
                    ];
                }

                $groupName = $stat->group_name;
                if (!isset($statistics[$period]['groups'][$groupName])) {
                    $statistics[$period]['groups'][$groupName] = [
                        'groupName' => $groupName,
                        'statisticsItems' => []
                    ];
                }

                $statistics[$period]['groups'][$groupName]['statisticsItems'][] = [
                    'name' => $stat->statistic_name,
                    'home' => $stat->home,
                    'away' => $stat->away,
                    'compareCode' => $stat->compare_code,
                    'statisticsType' => $stat->statistics_type,
                    'valueType' => $stat->value_type,
                    'homeValue' => $stat->home_value,
                    'awayValue' => $stat->away_value,
                    'homeTotal' => $stat->home_total,
                    'awayTotal' => $stat->away_total,
                    'renderType' => $stat->render_type,
                    'key' => $stat->key
                ];
            }

            // Convert to array format
            $result = [];
            foreach ($statistics as $period => $periodData) {
                $periodData['groups'] = array_values($periodData['groups']);
                $result[] = $periodData;
            }
            return $result;
        }

        // Get fixture to get id_fixture
        $fixture = $this->fixtureRepository->findById($fixtureId);
        if (!$fixture || !$fixture->id_fixture) {
            Log::error("Fixture not found or id_fixture is missing for fixture ID: {$fixtureId}");
            return null;
        }

        // If not found, fetch from API
        try {
            $response = Http::withHeaders([
                'x-rapidapi-host' => "sofascore.p.rapidapi.com",
                "x-rapidapi-key" => '3ffcbe8639mshed1c7dc03a94db6p16d136jsn775d46322204'
            ])->get("https://sofascore.p.rapidapi.com/matches/get-statistics?matchId={$fixture->id_fixture}");

            if (!$response->successful()) {
                Log::error("Failed to fetch fixture statistics: {$response->status()}");
                return null;
            }

            $data = $response->json();
            if (empty($data['statistics'])) {
                return null;
            }

            $statistics = $data['statistics'];

            // Save statistics to database
            $this->saveFixtureStatistics($fixtureId, $statistics);

            return $statistics;
        } catch (\Exception $e) {
            Log::error("Error fetching fixture statistics: {$e->getMessage()}");
            return null;
        }
    }


    public function saveLineupFromApi($fixtureId, $lineupData)
    {
        try {
            DB::beginTransaction();

            // Get fixture to get correct team IDs
            $fixture = $this->fixtureRepository->findById($fixtureId);
            if (!$fixture) {
                throw new \Exception("Fixture not found");
            }

            // Save home team lineup
            if (isset($lineupData['home'])) {
                $homeLineup = $this->lineupRepository->create([
                    'fixture_id' => $fixtureId,
                    'team_id' => $fixture->home_team_id,
                    'formation' => $lineupData['home']['formation'] ?? null
                ]);

                // Process home team starting XI
                $positionCounters = [
                    'G' => 0,
                    'D' => 0,
                    'M' => 0,
                    'F' => 0
                ];

                foreach ($lineupData['home']['players'] as $player) {
                    if (!$player['substitute']) {
                        $positionType = $player['position'];
                        if ($positionType) {
                            $positionCounters[$positionType]++;
                            $positionNumber = $this->getPositionNumber($positionType, $positionCounters[$positionType]);

                            // Find or create person
                            $person = $this->personRepository->findByName($player['player']['name']);
                            if (!$person) {
                                $id = $this->personRepository->genAutoId();
                                $person = $this->personRepository->create([
                                    'id' => $id,
                                    'name' => $player['player']['name'],
                                    'type' => 'player',
                                    'nationality' => $player['player']['country']['name'] ?? null,
                                    'date_of_birth' => isset($player['player']['dateOfBirthTimestamp']) ?
                                        date('Y-m-d', $player['player']['dateOfBirthTimestamp']) : null,
                                    'last_updated' => now()
                                ]);
                            }

                            // Create or update person_team relationship
                            $this->personRepository->createOrUpdatePersonTeam([
                                'person_id' => $person->id,
                                'team_id' => $fixture->home_team_id,
                            ]);
                            // dd(($player['player']['statistics']));

                            // Create or update lineup player
                            $this->lineUpPlayerRepository->updateOrCreate(
                                [
                                    'lineup_id' => $homeLineup->id,
                                    'player_id' => $person->id
                                ],
                                [
                                    'position' => $person->position ?? null,
                                    'grid_position' => $positionNumber,
                                    'shirt_number' => $player['player']['jerseyNumber'],
                                    'is_substitute' => false,
                                    'statistics' => ($player['statistics']),
                                    'last_updated' => now()
                                ]
                            );
                        }
                    }
                }

                // Process home team substitutes
                $positionCounters = [
                    'G' => 0,
                    'D' => 0,
                    'M' => 0,
                    'F' => 0
                ];

                foreach ($lineupData['home']['players'] as $player) {
                    if ($player['substitute']) {
                        $positionType = $player['position'];
                        if ($positionType) {
                            $positionCounters[$positionType]++;
                            $positionNumber = $this->getPositionNumber($positionType, $positionCounters[$positionType]);

                            // Find or create person
                            $person = $this->personRepository->findByName($player['player']['name']);
                            if (!$person) {
                                $id = $this->personRepository->genAutoId();
                                $person = $this->personRepository->create([
                                    'id' => $id,
                                    'name' => $player['player']['name'],
                                    'type' => 'player',
                                    'nationality' => $player['player']['country']['name'] ?? null,
                                    'date_of_birth' => isset($player['player']['dateOfBirthTimestamp']) ?
                                        date('Y-m-d', $player['player']['dateOfBirthTimestamp']) : null,
                                    'last_updated' => now(),

                                ]);
                            }

                            // Create or update person_team relationship
                            $this->personRepository->createOrUpdatePersonTeam([
                                'person_id' => $person->id,
                                'team_id' => $fixture->home_team_id,
                            ]);

                            // Create or update lineup player
                            $this->lineUpPlayerRepository->updateOrCreate(
                                [
                                    'lineup_id' => $homeLineup->id,
                                    'player_id' => $person->id
                                ],
                                [
                                    'position' => $person->position ?? null,
                                    'grid_position' => null,
                                    'shirt_number' => $player['player']['jerseyNumber'],
                                    'is_substitute' => true,
                                    'last_updated' => now(),
                                    'statistics' => ($player['statistics']) ?? null,

                                ]
                            );
                        }
                    }
                }
            }

            // Save away team lineup
            if (isset($lineupData['away'])) {
                $awayLineup = $this->lineupRepository->create([
                    'fixture_id' => $fixtureId,
                    'team_id' => $fixture->away_team_id,
                    'formation' => $lineupData['away']['formation'] ?? null
                ]);

                // Process away team starting XI
                $positionCounters = [
                    'G' => 0,
                    'D' => 0,
                    'M' => 0,
                    'F' => 0
                ];

                foreach ($lineupData['away']['players'] as $player) {
                    if (!$player['substitute']) { // Fixed condition to only process starting XI players
                        $positionType = $player['position'];
                        if ($positionType) {
                            $positionCounters[$positionType]++;
                            $positionNumber = $this->getPositionNumber($positionType, $positionCounters[$positionType]);

                            // Find or create person
                            $person = $this->personRepository->findByName($player['player']['name']);
                            if (!$person) {
                                $id = $this->personRepository->genAutoId();
                                $person = $this->personRepository->create([
                                    'id' => $id,
                                    'name' => $player['player']['name'],
                                    'type' => 'player',
                                    'nationality' => $player['player']['country']['name'] ?? null,
                                    'date_of_birth' => isset($player['player']['dateOfBirthTimestamp']) ?
                                        date('Y-m-d', $player['player']['dateOfBirthTimestamp']) : null,
                                    'last_updated' => now()
                                ]);
                            }

                            // Create or update person_team relationship
                            $this->personRepository->createOrUpdatePersonTeam([
                                'person_id' => $person->id,
                                'team_id' => $fixture->away_team_id,
                            ]);

                            // Create or update lineup player
                            $this->lineUpPlayerRepository->updateOrCreate(
                                [
                                    'lineup_id' => $awayLineup->id,
                                    'player_id' => $person->id
                                ],
                                [
                                    'position' => $person->position ?? null,
                                    'grid_position' => $positionNumber,
                                    'shirt_number' => $player['player']['jerseyNumber'],
                                    'is_substitute' => false,
                                    'last_updated' => now(),
                                    'statistics' => ($player['statistics']) ?? null,

                                ]
                            );
                        }
                    }
                }

                // Process away team substitutes
                $positionCounters = [
                    'G' => 0,
                    'D' => 0,
                    'M' => 0,
                    'F' => 0
                ];

                foreach ($lineupData['away']['players'] as $player) {
                    if ($player['substitute']) {
                        $positionType = $player['position'];
                        if ($positionType) {
                            $positionCounters[$positionType]++;
                            $positionNumber = $this->getPositionNumber($positionType, $positionCounters[$positionType]);

                            // Find or create person
                            $person = $this->personRepository->findByName($player['player']['name']);
                            if (!$person) {
                                $id = $this->personRepository->genAutoId();
                                $person = $this->personRepository->create([
                                    'id' => $id,
                                    'name' => $player['player']['name'],
                                    'type' => 'player',
                                    'nationality' => $player['player']['country']['name'] ?? null,
                                    'date_of_birth' => isset($player['player']['dateOfBirthTimestamp']) ?
                                        date('Y-m-d', $player['player']['dateOfBirthTimestamp']) : null,
                                    'last_updated' => now()
                                ]);
                            }

                            // Create or update person_team relationship
                            $this->personRepository->createOrUpdatePersonTeam([
                                'person_id' => $person->id,
                                'team_id' => $fixture->away_team_id,
                            ]);

                            // Create or update lineup player
                            $this->lineUpPlayerRepository->updateOrCreate(
                                [
                                    'lineup_id' => $awayLineup->id,
                                    'player_id' => $person->id
                                ],
                                [
                                    'position' => $person->position ?? null,
                                    'grid_position' => null, // Substitutes don't have grid positions
                                    'shirt_number' => $player['player']['jerseyNumber'],
                                    'is_substitute' => true,
                                    'last_updated' => now(),
                                    'statistics' => ($player['statistics']) ?? null,

                                ]
                            );
                        }
                    }
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saving lineup from API for fixture {$fixtureId}: " . $e->getMessage());
            throw $e;
        }
    }


    private function getPositionNumber($positionType, $counter)
    {
        $baseNumber = [
            'G' => 1,
            'D' => 2,
            'M' => 3,
            'F' => 4
        ];

        return $baseNumber[$positionType] . ':' . $counter;
    }

    public function getLineUpByFixtureId($fixtureId)
    {

        $fixture = $this->fixtureRepository->findById($fixtureId);
        if (!$fixture) {
            return null;
        }
        if($fixture->status != 'FINISHED'){
            return null;
        }
        if (!$fixture->homeLineup || !$fixture->awayLineup) {
            $response = Http::withHeaders([
                'x-rapidapi-host' => "sofascore.p.rapidapi.com",
                "x-rapidapi-key" => '3ffcbe8639mshed1c7dc03a94db6p16d136jsn775d46322204'
            ])->get("https://sofascore.p.rapidapi.com/matches/get-lineups?matchId={$fixture->id_fixture}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['confirmed'])) {
                    $this->saveLineupFromApi($fixtureId, $data);

                    $fixture = $this->fixtureRepository->findById($fixtureId);
                }
            }
        }
        if ($fixture->homeLineup && $fixture->awayLineup) {

            return [
                'home_lineup' => collect($this->mapLineupToArray($fixture->homeLineup)) ?? null,
                'away_lineup' => collect($this->mapLineupToArray($fixture->awayLineup)) ?? null,
            ];
        } else {
            return null;
        }
    }

    /**
     * Get a competition by name
     *
     * @param string $name
     * @return \App\Models\Competition|null
     */
    public function getCompetitionByName(string $name)
    {
        return $this->competitionService->findCompetitionByName($name);
    }
}
